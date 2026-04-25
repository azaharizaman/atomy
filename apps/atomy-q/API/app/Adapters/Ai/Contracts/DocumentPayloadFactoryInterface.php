<?php

declare(strict_types=1);

namespace App\Adapters\Ai\Contracts;

use App\Adapters\Ai\DTOs\DocumentExtractionRequest;

interface DocumentPayloadFactoryInterface
{
    /**
     * @return array<string, mixed>
     */
    public function build(DocumentExtractionRequest $request): array;
}
