<?php

declare(strict_types=1);

namespace OybekDaniyarov\LaravelTrpc\Collections;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use OybekDaniyarov\LaravelTrpc\Data\RouteData;
use Traversable;

/**
 * Collection of route data objects.
 *
 * @implements IteratorAggregate<int, RouteData>
 */
final class RouteCollection implements Countable, IteratorAggregate
{
    /** @var array<int, RouteData> */
    private array $routes = [];

    /** @var array<string, int> */
    private array $nameIndex = [];

    /**
     * @param  array<int, RouteData>  $routes
     */
    public function __construct(array $routes = [])
    {
        foreach ($routes as $route) {
            $this->add($route);
        }
    }

    /**
     * Add a route to the collection.
     */
    public function add(RouteData $route): self
    {
        $index = count($this->routes);
        $this->routes[] = $route;
        $this->nameIndex[$route->name] = $index;

        return $this;
    }

    /**
     * Get a route by name.
     */
    public function get(string $name): ?RouteData
    {
        if (! isset($this->nameIndex[$name])) {
            return null;
        }

        return $this->routes[$this->nameIndex[$name]];
    }

    /**
     * Check if a route exists by name.
     */
    public function has(string $name): bool
    {
        return isset($this->nameIndex[$name]);
    }

    /**
     * Get all routes.
     *
     * @return array<int, RouteData>
     */
    public function all(): array
    {
        return $this->routes;
    }

    /**
     * Filter routes by a callback.
     *
     * @param  callable(RouteData): bool  $callback
     */
    public function filter(callable $callback): self
    {
        return new self(array_filter($this->routes, $callback));
    }

    /**
     * Map routes to a new collection.
     *
     * @template T
     *
     * @param  callable(RouteData): T  $callback
     * @return array<int, T>
     */
    public function map(callable $callback): array
    {
        return array_map($callback, $this->routes);
    }

    /**
     * Group routes by a key.
     *
     * @param  callable(RouteData): string  $keySelector
     * @return array<string, self>
     */
    public function groupBy(callable $keySelector): array
    {
        $groups = [];

        foreach ($this->routes as $route) {
            $key = $keySelector($route);
            $groups[$key] ??= new self;
            $groups[$key]->add($route);
        }

        return $groups;
    }

    /**
     * Sort routes by name.
     */
    public function sortByName(): self
    {
        $routes = $this->routes;
        usort($routes, fn (RouteData $a, RouteData $b) => strcmp($a->name, $b->name));

        return new self($routes);
    }

    /**
     * Merge with another collection.
     */
    public function merge(self $other): self
    {
        $collection = new self($this->routes);

        foreach ($other->routes as $route) {
            if (! $collection->has($route->name)) {
                $collection->add($route);
            }
        }

        return $collection;
    }

    /**
     * Check if the collection is empty.
     */
    public function isEmpty(): bool
    {
        return empty($this->routes);
    }

    /**
     * Get the count of routes.
     */
    public function count(): int
    {
        return count($this->routes);
    }

    /**
     * Get an iterator.
     *
     * @return Traversable<int, RouteData>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->routes);
    }

    /**
     * Convert to array.
     *
     * @return array<int, array<string, mixed>>
     */
    public function toArray(): array
    {
        return array_map(fn (RouteData $route) => $route->toArray(), $this->routes);
    }
}
