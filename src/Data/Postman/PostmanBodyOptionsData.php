<?php

declare(strict_types=1);

namespace OybekDaniyarov\LaravelTrpc\Data\Postman;

use Spatie\LaravelData\Data;

/**
 * Postman body options.
 */
final class PostmanBodyOptionsData extends Data
{
    public function __construct(
        public ?PostmanBodyRawOptionsData $raw = null,
    ) {}
}
