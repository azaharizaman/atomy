<?php

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Adapters\Ai\ConfiguredAiEndpointRegistry;
use App\Adapters\Ai\ProviderAiTransport;
use Illuminate\Http\Client\Factory as HttpFactory;

echo "Testing token path through transport...\n";

// Get config from Laravel
$config = config('atomy.ai');
$registry = new ConfiguredAiEndpointRegistry($config);
$endpoint = $registry->endpointConfig('sourcing_recommendation');

$token = $endpoint->metadata['auth_token'] ?? null;
echo "Endpoint token (first 30): " . ($token ? substr($token, 0, 30) . "..." : "null") . "\n";
echo "Endpoint URI: " . $endpoint->endpointUri . "\n";

// Test if transport sends auth correctly by adding debug
echo "\nTest 1: Using Guzzle withToken()...\n";
$client = new HttpFactory();
$payload = [
    'model' => 'google/gemma-4-26b-a4b-it:free',
    'messages' => [['role' => 'user', 'content' => 'hi']],
    'max_tokens' => 10,
];
$response = $client->acceptJson()
    ->asJson()
    ->withToken($token)
    ->timeout(30)
    ->post($endpoint->endpointUri, $payload);
echo "Status: " . $response->status() . "\n";
echo "Body: " . substr($response->body(), 0, 200) . "\n";

echo "\nTest 2: Using Bearer header (for comparison)...\n";
$response2 = $client->acceptJson()
    ->asJson()
    ->withHeaders(['Authorization' => 'Bearer ' . $token])
    ->timeout(30)
    ->post($endpoint->endpointUri, $payload);
echo "Status: " . $response2->status() . "\n";
echo "Body: " . substr($response2->body(), 0, 200) . "\n";