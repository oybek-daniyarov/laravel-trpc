<?php

declare(strict_types=1);

namespace OybekDaniyarov\LaravelTrpc\Collections;

use InvalidArgumentException;
use OybekDaniyarov\LaravelTrpc\Contracts\Generator;

/**
 * Collection of output generators.
 *
 * Manages generator instances and allows retrieval by name.
 */
final class GeneratorCollection
{
    /** @var array<string, Generator> */
    private array $generators = [];

    /**
     * Add a generator with a name.
     */
    public function add(string $name, Generator $generator): self
    {
        $this->generators[$name] = $generator;

        return $this;
    }

    /**
     * Get a generator by name.
     *
     * @throws InvalidArgumentException if generator not found
     */
    public function get(string $name): Generator
    {
        if (! isset($this->generators[$name])) {
            throw new InvalidArgumentException("Generator '{$name}' not found.");
        }

        return $this->generators[$name];
    }

    /**
     * Check if a generator exists.
     */
    public function has(string $name): bool
    {
        return isset($this->generators[$name]);
    }

    /**
     * Get all generators.
     *
     * @return array<string, Generator>
     */
    public function all(): array
    {
        return $this->generators;
    }

    /**
     * Get all generator names.
     *
     * @return array<int, string>
     */
    public function names(): array
    {
        return array_keys($this->generators);
    }
}
