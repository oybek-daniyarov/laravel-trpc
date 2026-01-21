<?php

declare(strict_types=1);

namespace OybekDaniyarov\LaravelTrpc\Data\Postman;

use Illuminate\Support\Collection;
use Spatie\LaravelData\Data;

/**
 * Root Postman collection structure.
 *
 * @see https://schema.postman.com/json/collection/v2.1.0/docs/index.html
 */
final class PostmanCollectionData extends Data
{
    /**
     * @param  Collection<int, PostmanItemData|PostmanFolderData>  $item
     * @param  Collection<int, PostmanVariableData>|null  $variable
     * @param  Collection<int, PostmanEventData>|null  $event
     */
    public function __construct(
        public PostmanInfoData $info,
        public Collection $item,
        public ?Collection $variable = null,
        public ?PostmanAuthData $auth = null,
        public ?Collection $event = null,
    ) {}
}
