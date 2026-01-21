<?php

declare(strict_types=1);

namespace OybekDaniyarov\LaravelTrpc\Data\Postman;

use Spatie\LaravelData\Data;

/**
 * Postman formdata item for multipart form requests.
 */
final class PostmanFormDataItemData extends Data
{
    public function __construct(
        public string $key,
        public string $type = 'text',
        public ?string $value = null,
        public ?string $src = null,
        public ?string $description = null,
    ) {}
}
