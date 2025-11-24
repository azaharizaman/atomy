<?php

declare(strict_types=1);

namespace Nexus\MachineLearning\Core\Engines;

use Nexus\MachineLearning\Contracts\InferenceEngineInterface;
use Nexus\MachineLearning\Exceptions\InferenceEngineUnavailableException;
use Nexus\MachineLearning\Exceptions\InferenceException;
use Nexus\MachineLearning\Exceptions\InferenceTimeoutException;
use Nexus\MachineLearning\ValueObjects\Model;
use Psr\Log\LoggerInterface;

/**
 * ONNX Runtime inference engine
 * 
 * Executes ONNX (Open Neural Network Exchange) models using Python subprocess
 * with onnxruntime package.
 * 
 * ONNX is a cross-platform format supporting models from:
 * - PyTorch (via torch.onnx.export)
 * - TensorFlow (via tf2onnx)
 * - scikit-learn (via skl2onnx)
 * - XGBoost, LightGBM, CatBoost
 * 
 * Requirements:
 * - Python 3.8+ installed
 * - onnxruntime package (pip install onnxruntime)
 * - Model saved as .onnx file
 * 
 * Performance:
 * - Generally faster than PyTorch for inference-only workloads
 * - Optimized for CPU inference
 * - GPU support available with onnxruntime-gpu
 */
final readonly class ONNXInferenceEngine implements InferenceEngineInterface
{
    private const PYTHON_EXECUTABLE = 'python3';
    private const DEFAULT_TIMEOUT = 30;

    /**
     * @param string|null $pythonPath Path to Python executable (null for system default)
     * @param int $timeout Maximum execution time in seconds
     * @param LoggerInterface|null $logger Optional logger
     */
    public function __construct(
        private ?string $pythonPath = null,
        private int $timeout = self::DEFAULT_TIMEOUT,
        private ?LoggerInterface $logger = null,
    ) {}

    /**
     * {@inheritDoc}
     */
    public function predict(Model $model, array $input): array
    {
        if (!$this->isAvailable()) {
            throw InferenceEngineUnavailableException::missingDependency(
                'onnx',
                'Python 3.8+ with onnxruntime package',
                'pip install onnxruntime'
            );
        }

        $pythonScript = $this->generatePredictionScript($model, [$input], batch: false);
        $result = $this->executePythonScript($pythonScript, $model);

        return $result[0] ?? [];
    }

    /**
     * {@inheritDoc}
     */
    public function batchPredict(Model $model, array $inputs): array
    {
        if (!$this->isAvailable()) {
            throw InferenceEngineUnavailableException::missingDependency(
                'onnx',
                'Python 3.8+ with onnxruntime package',
                'pip install onnxruntime'
            );
        }

        $pythonScript = $this->generatePredictionScript($model, $inputs, batch: true);
        return $this->executePythonScript($pythonScript, $model);
    }

    /**
     * {@inheritDoc}
     */
    public function supportsFormat(string $modelFormat): bool
    {
        return in_array($modelFormat, ['onnx'], true);
    }

    /**
     * {@inheritDoc}
     */
    public function isAvailable(): bool
    {
        try {
            $pythonExec = $this->pythonPath ?? self::PYTHON_EXECUTABLE;
            
            // Check Python availability
            $output = shell_exec("{$pythonExec} --version 2>&1");
            if ($output === null || !str_contains($output, 'Python')) {
                return false;
            }

            // Check onnxruntime package
            $checkOnnx = "{$pythonExec} -c \"import onnxruntime; print(onnxruntime.__version__)\" 2>&1";
            $onnxOutput = shell_exec($checkOnnx);
            
            return $onnxOutput !== null && !str_contains($onnxOutput, 'Error') && !str_contains($onnxOutput, 'No module');
            
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return 'onnx';
    }

    /**
     * {@inheritDoc}
     */
    public function warmUp(Model $model): void
    {
        try {
            $dummyInput = $this->generateDummyInput($model);
            $this->predict($model, $dummyInput);
            
            $this->logger?->info('ONNX model warmed up', [
                'model' => $model->getIdentifier(),
            ]);
        } catch (\Throwable $e) {
            $this->logger?->warning('Failed to warm up ONNX model', [
                'model' => $model->getIdentifier(),
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Generate Python script for ONNX prediction
     * 
     * @param Model $model
     * @param array<array<string, mixed>> $inputs
     * @param bool $batch
     * 
     * @return string Python code
     */
    private function generatePredictionScript(Model $model, array $inputs, bool $batch): string
    {
        $modelPath = $model->artifactPath;
        $inputJson = json_encode($inputs, JSON_THROW_ON_ERROR);

        return <<<PYTHON
import onnxruntime as ort
import numpy as np
import json
import sys

try:
    # Load ONNX model
    session = ort.InferenceSession('{$modelPath}')
    
    # Get input name from model
    input_name = session.get_inputs()[0].name
    
    # Parse input
    inputs = json.loads('''{$inputJson}''')
    
    # Convert to numpy array
    if isinstance(inputs, list):
        # Batch prediction
        features_list = []
        for inp in inputs:
            features = [float(v) for v in inp.values()]
            features_list.append(features)
        input_array = np.array(features_list, dtype=np.float32)
    else:
        # Single prediction
        features = [float(v) for v in inputs.values()]
        input_array = np.array([features], dtype=np.float32)
    
    # Run inference
    outputs = session.run(None, {input_name: input_array})
    
    # Convert output to list
    predictions = outputs[0].tolist()
    
    # Format results
    if {$batch}:
        results = [{'prediction': pred} if isinstance(pred, (int, float)) else pred for pred in predictions]
    else:
        pred = predictions[0]
        results = [{'prediction': pred} if isinstance(pred, (int, float)) else pred]
    
    print(json.dumps(results))
    
except Exception as e:
    print(json.dumps({'error': str(e)}), file=sys.stderr)
    sys.exit(1)
PYTHON;
    }

    /**
     * Execute Python script and return results
     * 
     * @param string $script Python code to execute
     * @param Model $model Model being executed (for error reporting)
     * 
     * @return array<array<string, mixed>> Prediction results
     * 
     * @throws InferenceException
     * @throws InferenceTimeoutException
     */
    private function executePythonScript(string $script, Model $model): array
    {
        $pythonExec = $this->pythonPath ?? self::PYTHON_EXECUTABLE;
        
        // Create temporary script file
        $tempFile = tempnam(sys_get_temp_dir(), 'onnx_');
        file_put_contents($tempFile, $script);
        
        try {
            $startTime = microtime(true);
            
            // Execute with timeout
            $command = "{$pythonExec} {$tempFile} 2>&1";
            $output = shell_exec($command);
            
            $duration = microtime(true) - $startTime;
            
            if ($duration > $this->timeout) {
                throw InferenceTimeoutException::forOperation('ONNX inference', $this->timeout);
            }
            
            if ($output === null) {
                throw InferenceException::forModel($model->getIdentifier(), 'Python execution failed');
            }
            
            $decoded = json_decode($output, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw InferenceException::forModel($model->getIdentifier(), "Invalid JSON output: {$output}");
            }
            
            if (isset($decoded['error'])) {
                throw InferenceException::forModel($model->getIdentifier(), $decoded['error']);
            }
            
            return $decoded;
            
        } finally {
            @unlink($tempFile);
        }
    }

    /**
     * Generate dummy input for warm-up
     * 
     * @param Model $model
     * 
     * @return array<string, mixed>
     */
    private function generateDummyInput(Model $model): array
    {
        $inputSchema = $model->getInputSchema();
        
        if (empty($inputSchema)) {
            return ['feature1' => 0.0, 'feature2' => 0.0];
        }
        
        $dummy = [];
        foreach ($inputSchema as $feature => $type) {
            $dummy[$feature] = 0.0;
        }
        
        return $dummy;
    }
}
