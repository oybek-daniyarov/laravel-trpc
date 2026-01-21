<?php

declare(strict_types=1);

namespace OybekDaniyarov\LaravelTrpc\Contracts;

use OybekDaniyarov\LaravelTrpc\Collections\RouteCollection;
use OybekDaniyarov\LaravelTrpc\Data\RouteTypeInfo;

/**
 * Contract for route collectors.
 *
 * Route collectors are responsible for discovering and collecting routes
 * from the application that should be included in the generated output.
 */
interface Collector
{
    /**
     * Collect routes from the application.
     *
     * @param  array<string, RouteTypeInfo>  $routeTypes  Pre-extracted route type information
     */
    public function collect(array $routeTypes = []): RouteCollection;
}
