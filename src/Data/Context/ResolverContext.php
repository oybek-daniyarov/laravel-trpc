<?php

declare(strict_types=1);

namespace OybekDaniyarov\LaravelTrpc\Data\Context;

use OybekDaniyarov\LaravelTrpc\TrpcConfig;

/**
 * Context object for type resolvers.
 *
 * Provides resolvers with configuration and context needed
 * to properly resolve PHP types to TypeScript types.
 */
final readonly class ResolverContext
{
    /**
     * @param  array<string, string>  $typeReplacements
     */
    public function __construct(
        public ?TrpcConfig $config = null,
        public array $typeReplacements = [],
        public bool $isNullable = false,
        public bool $isGeneric = false,
        public string $currentClass = '',
        public string $currentMethod = '',
    ) {}

    /**
     * Create a new context marking the type as nullable.
     */
    public function asNullable(): self
    {
        return new self(
            config: $this->config,
            typeReplacements: $this->typeReplacements,
            isNullable: true,
            isGeneric: $this->isGeneric,
            currentClass: $this->currentClass,
            currentMethod: $this->currentMethod,
        );
    }

    /**
     * Create a new context for a specific class and method.
     */
    public function forMethod(string $class, string $method): self
    {
        return new self(
            config: $this->config,
            typeReplacements: $this->typeReplacements,
            isNullable: $this->isNullable,
            isGeneric: $this->isGeneric,
            currentClass: $class,
            currentMethod: $method,
        );
    }
}
