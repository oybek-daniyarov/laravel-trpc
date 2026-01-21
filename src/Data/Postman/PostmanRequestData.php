<?php

declare(strict_types=1);

namespace OybekDaniyarov\LaravelTrpc\Data\Postman;

use Illuminate\Support\Collection;
use Spatie\LaravelData\Data;

/**
 * Postman request configuration.
 */
final class PostmanRequestData extends Data
{
    /**
     * @param  Collection<int, PostmanHeaderData>  $header
     */
    public function __construct(
        public string $method,
        public PostmanUrlData $url,
        public Collection $header,
        public ?PostmanBodyData $body = null,
        public ?PostmanAuthData $auth = null,
        public ?string $description = null,
    ) {}
}
