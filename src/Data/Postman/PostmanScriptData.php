<?php

declare(strict_types=1);

namespace OybekDaniyarov\LaravelTrpc\Data\Postman;

use Illuminate\Support\Collection;
use Spatie\LaravelData\Data;

/**
 * Postman script (pre-request or test).
 */
final class PostmanScriptData extends Data
{
    /**
     * @param  Collection<int, string>  $exec
     */
    public function __construct(
        public string $type,
        public Collection $exec,
    ) {}

    /**
     * Create a pre-request script.
     *
     * @param  array<int, string>  $lines
     */
    public static function prerequest(array $lines): self
    {
        return new self(
            type: 'text/javascript',
            exec: collect($lines),
        );
    }

    /**
     * Create a test script.
     *
     * @param  array<int, string>  $lines
     */
    public static function test(array $lines): self
    {
        return new self(
            type: 'text/javascript',
            exec: collect($lines),
        );
    }
}
