<?php

declare(strict_types=1);

namespace OybekDaniyarov\LaravelTrpc\Contracts;

use OybekDaniyarov\LaravelTrpc\Data\Context\TransformContext;

/**
 * Contract for type transformers.
 *
 * Transformers convert PHP types into their TypeScript representations.
 * They can be registered for specific PHP types to provide custom
 * transformation logic.
 */
interface Transformer
{
    /**
     * Transform a PHP type into a TypeScript type string.
     */
    public function transform(mixed $value, TransformContext $context): string;

    /**
     * Check if this transformer supports the given type.
     */
    public function supports(string $type): bool;
}
