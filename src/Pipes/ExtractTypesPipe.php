<?php

declare(strict_types=1);

namespace OybekDaniyarov\LaravelTrpc\Pipes;

use Closure;
use OybekDaniyarov\LaravelTrpc\Contracts\Pipe;
use OybekDaniyarov\LaravelTrpc\Data\PipelinePayload;
use OybekDaniyarov\LaravelTrpc\Services\RouteTypeExtractor;

/**
 * Pipe that extracts type information from routes.
 *
 * Uses Laravel Ranger and Surveyor for static analysis.
 */
final class ExtractTypesPipe implements Pipe
{
    public function __construct(
        private readonly RouteTypeExtractor $typeExtractor,
    ) {}

    public function handle(PipelinePayload $payload, Closure $next): PipelinePayload
    {
        // Extract types for all routes
        $routeTypes = $this->typeExtractor->extractRouteTypes();

        // Store in metadata for later use
        $payload->withMetadata('routeTypes', $routeTypes);

        return $next($payload);
    }
}
