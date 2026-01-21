<?php

declare(strict_types=1);

namespace OybekDaniyarov\LaravelTrpc\Data\Postman;

use Spatie\LaravelData\Data;

/**
 * Postman authentication item.
 */
final class PostmanAuthItemData extends Data
{
    public function __construct(
        public string $key,
        public string $value,
        public string $type = 'string',
    ) {}
}
