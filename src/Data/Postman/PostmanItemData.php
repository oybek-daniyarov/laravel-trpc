<?php

declare(strict_types=1);

namespace OybekDaniyarov\LaravelTrpc\Data\Postman;

use Illuminate\Support\Collection;
use Spatie\LaravelData\Data;

/**
 * Postman request item.
 */
final class PostmanItemData extends Data
{
    /**
     * @param  Collection<int, PostmanEventData>|null  $event
     */
    public function __construct(
        public string $name,
        public PostmanRequestData $request,
        public ?Collection $event = null,
    ) {}
}
