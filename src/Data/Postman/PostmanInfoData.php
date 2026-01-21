<?php

declare(strict_types=1);

namespace OybekDaniyarov\LaravelTrpc\Data\Postman;

use Spatie\LaravelData\Data;

/**
 * Postman collection info block.
 */
final class PostmanInfoData extends Data
{
    public function __construct(
        public string $name,
        public string $schema = 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
        public ?string $description = null,
    ) {}
}
