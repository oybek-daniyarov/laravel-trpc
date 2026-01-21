<?php

declare(strict_types=1);

namespace OybekDaniyarov\LaravelTrpc\Data\Postman;

use Illuminate\Support\Collection;
use Spatie\LaravelData\Data;

/**
 * Postman URL structure.
 */
final class PostmanUrlData extends Data
{
    /**
     * @param  Collection<int, string>  $host
     * @param  Collection<int, string>  $path
     * @param  Collection<int, PostmanUrlVariableData>|null  $variable
     * @param  Collection<int, PostmanQueryParamData>|null  $query
     */
    public function __construct(
        public string $raw,
        public Collection $host,
        public Collection $path,
        public ?Collection $variable = null,
        public ?Collection $query = null,
    ) {}
}
