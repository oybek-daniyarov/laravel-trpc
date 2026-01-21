<?php

declare(strict_types=1);

namespace OybekDaniyarov\LaravelTrpc\Data\Context;

use OybekDaniyarov\LaravelTrpc\Data\RouteData;
use OybekDaniyarov\LaravelTrpc\TrpcConfig;

/**
 * Context object for type transformers.
 *
 * Provides transformers with all the information they need to
 * properly transform types, including the current route and config.
 */
final readonly class TransformContext
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public ?RouteData $route = null,
        public ?TrpcConfig $config = null,
        public array $metadata = [],
    ) {}

    /**
     * Create a new context with the given route.
     */
    public function withRoute(RouteData $route): self
    {
        return new self(
            route: $route,
            config: $this->config,
            metadata: $this->metadata,
        );
    }

    /**
     * Create a new context with metadata.
     */
    public function withMetadata(string $key, mixed $value): self
    {
        return new self(
            route: $this->route,
            config: $this->config,
            metadata: array_merge($this->metadata, [$key => $value]),
        );
    }
}
