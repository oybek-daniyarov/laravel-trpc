<?php

declare(strict_types=1);

namespace OybekDaniyarov\LaravelTrpc\Data\Postman;

use Illuminate\Support\Collection;
use Spatie\LaravelData\Data;

/**
 * Postman authentication configuration.
 */
final class PostmanAuthData extends Data
{
    /**
     * @param  Collection<int, PostmanAuthItemData>|null  $bearer
     * @param  Collection<int, PostmanAuthItemData>|null  $apikey
     * @param  Collection<int, PostmanAuthItemData>|null  $basic
     */
    public function __construct(
        public string $type,
        public ?Collection $bearer = null,
        public ?Collection $apikey = null,
        public ?Collection $basic = null,
    ) {}

    /**
     * Create "no auth" - explicitly disable authentication for public endpoints.
     */
    public static function noauth(): self
    {
        return new self(type: 'noauth');
    }

    /**
     * Create bearer token authentication.
     */
    public static function bearer(string $tokenVariable = '{{token}}'): self
    {
        return new self(
            type: 'bearer',
            bearer: collect([
                new PostmanAuthItemData(key: 'token', value: $tokenVariable, type: 'string'),
            ]),
        );
    }

    /**
     * Create API key authentication.
     */
    public static function apikey(string $keyName = 'X-API-Key', string $keyVariable = '{{api_key}}'): self
    {
        return new self(
            type: 'apikey',
            apikey: collect([
                new PostmanAuthItemData(key: 'key', value: $keyName, type: 'string'),
                new PostmanAuthItemData(key: 'value', value: $keyVariable, type: 'string'),
                new PostmanAuthItemData(key: 'in', value: 'header', type: 'string'),
            ]),
        );
    }
}
