<?php

declare(strict_types=1);

namespace OybekDaniyarov\LaravelTrpc\Transformers;

use OybekDaniyarov\LaravelTrpc\Contracts\Transformer;
use OybekDaniyarov\LaravelTrpc\Data\Context\TransformContext;
use Spatie\LaravelData\Data;

/**
 * Transformer for Spatie Laravel Data classes.
 *
 * Converts Data class names to their TypeScript-friendly format.
 */
final class DataTransformer implements Transformer
{
    public function transform(mixed $value, TransformContext $context): string
    {
        if (! is_string($value) || ! class_exists($value)) {
            return 'unknown';
        }

        // Convert namespace to dot notation
        return str_replace('\\', '.', mb_ltrim($value, '\\'));
    }

    public function supports(string $type): bool
    {
        return class_exists($type) && is_subclass_of($type, Data::class);
    }
}
