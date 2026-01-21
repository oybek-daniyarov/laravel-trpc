<?php

declare(strict_types=1);

namespace OybekDaniyarov\LaravelTrpc\Data\Postman;

use Illuminate\Support\Collection;
use Spatie\LaravelData\Data;

/**
 * Postman request body.
 *
 * Supports two modes:
 * - raw: JSON body for standard API requests
 * - formdata: Multipart form for file uploads
 */
final class PostmanBodyData extends Data
{
    /**
     * @param  Collection<int, PostmanFormDataItemData>|null  $formdata
     */
    public function __construct(
        public string $mode,
        public ?string $raw = null,
        public ?Collection $formdata = null,
        public ?PostmanBodyOptionsData $options = null,
    ) {}

    /**
     * Create a raw JSON body.
     */
    public static function raw(string $json): self
    {
        return new self(
            mode: 'raw',
            raw: $json,
            options: new PostmanBodyOptionsData(
                raw: new PostmanBodyRawOptionsData(language: 'json')
            ),
        );
    }

    /**
     * Create a formdata body for file uploads.
     *
     * @param  Collection<int, PostmanFormDataItemData>  $formdata
     */
    public static function formdata(Collection $formdata): self
    {
        return new self(
            mode: 'formdata',
            formdata: $formdata,
        );
    }
}
