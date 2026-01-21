<?php

declare(strict_types=1);

namespace OybekDaniyarov\LaravelTrpc\Transformers;

use BackedEnum;
use OybekDaniyarov\LaravelTrpc\Contracts\Transformer;
use OybekDaniyarov\LaravelTrpc\Data\Context\TransformContext;
use ReflectionEnum;

/**
 * Transformer for PHP backed enums.
 *
 * Converts backed enums to their backing type value.
 */
final class EnumTransformer implements Transformer
{
    public function transform(mixed $value, TransformContext $context): string
    {
        if (! is_string($value) || ! class_exists($value)) {
            return 'unknown';
        }

        if (! is_subclass_of($value, BackedEnum::class)) {
            return 'unknown';
        }

        $cases = $value::cases();
        $backingType = (new ReflectionEnum($value))->getBackingType();

        if ($backingType === null) {
            return 'string';
        }

        $isString = $backingType->getName() === 'string';
        $values = array_map(
            fn ($case) => $isString ? "'{$case->value}'" : (string) $case->value,
            $cases
        );

        return implode(' | ', $values);
    }

    public function supports(string $type): bool
    {
        return class_exists($type) && is_subclass_of($type, BackedEnum::class);
    }
}
