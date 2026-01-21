<?php

declare(strict_types=1);

namespace OybekDaniyarov\LaravelTrpc\Data\Postman;

use Spatie\LaravelData\Data;

/**
 * Postman collection variable.
 */
final class PostmanVariableData extends Data
{
    public function __construct(
        public string $key,
        public string $value = '',
        public ?string $type = 'string',
        public ?string $description = null,
    ) {}
}
