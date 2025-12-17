<?php

declare(strict_types=1);

namespace Nexus\DataPrivacy\Services;

use Nexus\DataPrivacy\Contracts\RequestHandlerInterface;
use Nexus\DataPrivacy\ValueObjects\DataSubjectRequest;
use Nexus\DataPrivacy\Enums\RequestType;
use Nexus\DataPrivacy\Exceptions\InvalidRequestException;

/**
 * Registry that routes data subject requests to appropriate handlers.
 */
final class RequestHandlerRegistry
{
    /** @var array<RequestHandlerInterface> */
    private array $handlers = [];

    /**
     * Register a request handler.
     */
    public function register(RequestHandlerInterface $handler): void
    {
        $this->handlers[] = $handler;
    }

    /**
     * Get the handler that supports the given request type.
     *
     * @throws InvalidRequestException If no handler supports the request type
     */
    public function getHandler(RequestType $type): RequestHandlerInterface
    {
        foreach ($this->handlers as $handler) {
            if ($handler->supports($type)) {
                return $handler;
            }
        }

        throw new InvalidRequestException(
            "No handler registered for request type: {$type->value}"
        );
    }

    /**
     * Check if a handler is registered for the given request type.
     */
    public function hasHandler(RequestType $type): bool
    {
        foreach ($this->handlers as $handler) {
            if ($handler->supports($type)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Execute a data subject request using the appropriate handler.
     *
     * @throws InvalidRequestException If no handler supports the request
     */
    public function execute(DataSubjectRequest $request): array
    {
        $handler = $this->getHandler($request->type);
        return $handler->execute($request);
    }

    /**
     * Validate a data subject request using the appropriate handler.
     *
     * @return array<string> Validation errors (empty if valid)
     * @throws InvalidRequestException If no handler supports the request
     */
    public function validate(DataSubjectRequest $request): array
    {
        $handler = $this->getHandler($request->type);
        return $handler->validate($request);
    }

    /**
     * Get supported request types.
     *
     * @return array<RequestType>
     */
    public function getSupportedTypes(): array
    {
        $types = [];

        foreach (RequestType::cases() as $type) {
            if ($this->hasHandler($type)) {
                $types[] = $type;
            }
        }

        return $types;
    }

    /**
     * Get all registered handlers.
     *
     * @return array<RequestHandlerInterface>
     */
    public function getHandlers(): array
    {
        return $this->handlers;
    }
}
