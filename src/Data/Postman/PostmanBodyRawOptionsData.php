<?php

declare(strict_types=1);

namespace OybekDaniyarov\LaravelTrpc\Data\Postman;

use Spatie\LaravelData\Data;

/**
 * Postman raw body options.
 */
final class PostmanBodyRawOptionsData extends Data
{
    public function __construct(
        public string $language = 'json',
    ) {}
}
