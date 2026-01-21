<?php

declare(strict_types=1);

namespace OybekDaniyarov\LaravelTrpc\Data\Postman;

use Spatie\LaravelData\Data;

/**
 * Postman URL path variable.
 */
final class PostmanUrlVariableData extends Data
{
    public function __construct(
        public string $key,
        public string $value = '',
        public ?string $description = null,
    ) {}
}
