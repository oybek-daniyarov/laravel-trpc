<?php

declare(strict_types=1);

namespace OybekDaniyarov\LaravelTrpc\Contracts;

use OybekDaniyarov\LaravelTrpc\Collections\RouteCollection;
use OybekDaniyarov\LaravelTrpc\Data\Context\GeneratorContext;
use OybekDaniyarov\LaravelTrpc\Data\GeneratorResult;

/**
 * Contract for output generators.
 *
 * Generators transform route collections into specific output formats
 * such as TypeScript definitions, Postman collections, or other formats.
 */
interface Generator
{
    /**
     * Generate output from the route collection.
     */
    public function generate(RouteCollection $routes, GeneratorContext $context): GeneratorResult;
}
