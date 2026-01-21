<?php

declare(strict_types=1);

namespace OybekDaniyarov\LaravelTrpc\Data\Postman;

use Illuminate\Support\Collection;
use Spatie\LaravelData\Data;

/**
 * Postman folder (group of requests).
 */
final class PostmanFolderData extends Data
{
    /**
     * @param  Collection<int, PostmanItemData|PostmanFolderData>  $item
     */
    public function __construct(
        public string $name,
        public Collection $item,
        public ?string $description = null,
    ) {}
}
