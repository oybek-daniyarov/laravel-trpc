<?php

declare(strict_types=1);

namespace OybekDaniyarov\LaravelTrpc\Data;

use OybekDaniyarov\LaravelTrpc\Collections\RouteCollection;
use OybekDaniyarov\LaravelTrpc\TrpcConfig;

/**
 * Payload that flows through the pipeline.
 *
 * Contains all the data needed by the pipeline pipes to process
 * and generate the final output.
 */
final class PipelinePayload
{
    public function __construct(
        public readonly TrpcConfig $config,
        public RouteCollection $routes,
        public GeneratorResult $result,
        /** @var array<string, mixed> */
        public array $metadata = [],
    ) {}

    /**
     * Create a new payload with initial state.
     */
    public static function create(TrpcConfig $config): self
    {
        return new self(
            config: $config,
            routes: new RouteCollection,
            result: new GeneratorResult([]),
        );
    }

    /**
     * Add metadata to the payload.
     */
    public function withMetadata(string $key, mixed $value): self
    {
        $this->metadata[$key] = $value;

        return $this;
    }

    /**
     * Get metadata from the payload.
     */
    public function getMetadata(string $key, mixed $default = null): mixed
    {
        return $this->metadata[$key] ?? $default;
    }
}
