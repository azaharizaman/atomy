<?php

declare(strict_types=1);

namespace Nexus\MachineLearning\Contracts;

use Nexus\MachineLearning\ValueObjects\Model;

/**
 * Inference engine interface for executing predictions on loaded models
 * 
 * Implementations support different model runtimes:
 * - PyTorch (via Python subprocess or FFI)
 * - ONNX Runtime (via onnxruntime PHP extension or FFI)
 * - Remote API (via HTTP client)
 * - TensorFlow (via TensorFlow Lite or saved_model)
 * 
 * Engines are responsible for:
 * - Model execution and prediction
 * - Input preprocessing and validation
 * - Output postprocessing
 * - Performance optimization (batching, threading)
 * 
 * Example usage:
 * ```php
 * $result = $engine->predict($model, ['feature1' => 1.5, 'feature2' => 0.8]);
 * ```
 */
interface InferenceEngineInterface
{
    /**
     * Execute prediction on a single input
     * 
     * @param Model $model The loaded model to use for inference
     * @param array<string, mixed> $input Input features as key-value pairs
     * 
     * @return array<string, mixed> Prediction result with confidence scores
     * 
     * @throws \Nexus\MachineLearning\Exceptions\InferenceException If prediction fails
     * @throws \Nexus\MachineLearning\Exceptions\InferenceTimeoutException If execution exceeds timeout
     */
    public function predict(Model $model, array $input): array;

    /**
     * Execute batch prediction on multiple inputs
     * 
     * @param Model $model The loaded model to use for inference
     * @param array<array<string, mixed>> $inputs Array of input feature sets
     * 
     * @return array<array<string, mixed>> Array of prediction results
     * 
     * @throws \Nexus\MachineLearning\Exceptions\InferenceException If prediction fails
     * @throws \Nexus\MachineLearning\Exceptions\InferenceTimeoutException If execution exceeds timeout
     */
    public function batchPredict(Model $model, array $inputs): array;

    /**
     * Check if the engine supports the given model format
     * 
     * @param string $modelFormat The model format (e.g., 'pytorch', 'onnx', 'tensorflow')
     * 
     * @return bool True if the engine can execute this model format
     */
    public function supportsFormat(string $modelFormat): bool;

    /**
     * Check if the engine runtime is available
     * 
     * Verifies that all required dependencies are installed:
     * - Python interpreter (for PyTorch, TensorFlow)
     * - ONNX Runtime library
     * - FFI extension
     * - Required Python packages
     * 
     * @return bool True if the runtime is available and functional
     */
    public function isAvailable(): bool;

    /**
     * Get the engine name
     * 
     * @return string Engine identifier (e.g., 'pytorch', 'onnx', 'remote_api')
     */
    public function getName(): string;

    /**
     * Warm up the engine (optional pre-loading, cache warming)
     * 
     * @param Model $model The model to warm up
     * 
     * @return void
     */
    public function warmUp(Model $model): void;
}
