<?php

declare(strict_types=1);

namespace OybekDaniyarov\LaravelTrpc\Contracts;

use OybekDaniyarov\LaravelTrpc\Data\Context\ResolverContext;
use ReflectionType;

/**
 * Contract for type resolvers.
 *
 * Type resolvers extract and format type information from PHP reflection
 * into TypeScript-compatible type strings.
 */
interface TypeResolver
{
    /**
     * Resolve a reflection type into a TypeScript type string.
     */
    public function resolve(?ReflectionType $type, ResolverContext $context): string;
}
