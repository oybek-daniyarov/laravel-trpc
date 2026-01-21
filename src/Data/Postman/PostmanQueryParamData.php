<?php

declare(strict_types=1);

namespace OybekDaniyarov\LaravelTrpc\Data\Postman;

use Spatie\LaravelData\Data;

/**
 * Postman query parameter structure.
 */
final class PostmanQueryParamData extends Data
{
    public function __construct(
        public string $key,
        public string $value = '',
        public ?string $description = null,
        public bool $disabled = false,
    ) {}
}
