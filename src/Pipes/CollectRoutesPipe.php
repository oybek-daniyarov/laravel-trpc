<?php

declare(strict_types=1);

namespace OybekDaniyarov\LaravelTrpc\Pipes;

use Closure;
use OybekDaniyarov\LaravelTrpc\Collections\RouteCollection;
use OybekDaniyarov\LaravelTrpc\Contracts\Collector;
use OybekDaniyarov\LaravelTrpc\Contracts\Pipe;
use OybekDaniyarov\LaravelTrpc\Data\PipelinePayload;
use OybekDaniyarov\LaravelTrpc\Data\RouteTypeInfo;

/**
 * Pipe that collects routes from all registered collectors.
 */
final class CollectRoutesPipe implements Pipe
{
    public function handle(PipelinePayload $payload, Closure $next): PipelinePayload
    {
        $collectors = $payload->config->getCollectors();
        $routes = new RouteCollection;

        // Get route types from metadata (extracted by ExtractTypesPipe)
        /** @var array<string, RouteTypeInfo> $routeTypes */
        $routeTypes = $payload->getMetadata('routeTypes', []);

        foreach ($collectors as $collectorClass) {
            /** @var Collector $collector */
            $collector = app($collectorClass, ['config' => $payload->config]);
            $routes = $routes->merge($collector->collect($routeTypes));
        }

        $payload->routes = $routes->sortByName();

        return $next($payload);
    }
}
