<?php

declare(strict_types=1);

namespace OybekDaniyarov\LaravelTrpc\Data\Postman;

use Spatie\LaravelData\Data;

/**
 * Postman request header.
 */
final class PostmanHeaderData extends Data
{
    public function __construct(
        public string $key,
        public string $value,
        public ?string $type = 'text',
        public ?string $description = null,
    ) {}
}
