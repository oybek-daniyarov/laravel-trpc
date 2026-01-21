<?php

declare(strict_types=1);

namespace OybekDaniyarov\LaravelTrpc\Data;

/**
 * Result of a generator operation.
 *
 * Contains the generated files as a map of filename to content.
 */
final readonly class GeneratorResult
{
    /**
     * @param  array<string, string>  $files  Map of filename to content
     */
    public function __construct(
        public array $files,
    ) {}

    /**
     * Merge with another generator result.
     */
    public function merge(self $other): self
    {
        return new self(
            array_merge($this->files, $other->files),
        );
    }

    /**
     * Check if the result is empty.
     */
    public function isEmpty(): bool
    {
        return empty($this->files);
    }

    /**
     * Get the total size of all generated files in bytes.
     */
    public function getTotalSize(): int
    {
        return array_sum(array_map('strlen', $this->files));
    }
}
