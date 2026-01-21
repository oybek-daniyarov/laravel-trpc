<?php

declare(strict_types=1);

namespace OybekDaniyarov\LaravelTrpc\Transformers;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use DateTimeImmutable;
use DateTimeInterface;
use Illuminate\Support\Carbon as IlluminateCarbon;
use OybekDaniyarov\LaravelTrpc\Contracts\Transformer;
use OybekDaniyarov\LaravelTrpc\Data\Context\TransformContext;

/**
 * Transformer for Carbon and DateTime classes.
 *
 * Converts all date/time types to string (ISO 8601 format).
 */
final class CarbonTransformer implements Transformer
{
    private const array SUPPORTED_TYPES = [
        Carbon::class,
        CarbonImmutable::class,
        IlluminateCarbon::class,
        DateTimeInterface::class,
        DateTimeImmutable::class,
        DateTimeImmutable::class,
    ];

    public function transform(mixed $value, TransformContext $context): string
    {
        return 'string';
    }

    public function supports(string $type): bool
    {
        foreach (self::SUPPORTED_TYPES as $supportedType) {
            if ($type === $supportedType || is_a($type, $supportedType, true)) {
                return true;
            }
        }

        return false;
    }
}
