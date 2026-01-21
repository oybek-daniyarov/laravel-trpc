<?php

declare(strict_types=1);

namespace OybekDaniyarov\LaravelTrpc\Data\Postman;

use Spatie\LaravelData\Data;

/**
 * Postman event (prerequest or test).
 */
final class PostmanEventData extends Data
{
    public function __construct(
        public string $listen,
        public PostmanScriptData $script,
    ) {}

    /**
     * Create a pre-request event.
     *
     * @param  array<int, string>  $lines
     */
    public static function prerequest(array $lines): self
    {
        return new self(
            listen: 'prerequest',
            script: PostmanScriptData::prerequest($lines),
        );
    }

    /**
     * Create a test event.
     *
     * @param  array<int, string>  $lines
     */
    public static function test(array $lines): self
    {
        return new self(
            listen: 'test',
            script: PostmanScriptData::test($lines),
        );
    }
}
