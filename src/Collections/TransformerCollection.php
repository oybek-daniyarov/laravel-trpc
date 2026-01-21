<?php

declare(strict_types=1);

namespace OybekDaniyarov\LaravelTrpc\Collections;

use OybekDaniyarov\LaravelTrpc\Contracts\Transformer;

/**
 * Collection of type transformers.
 *
 * Manages transformer instances and allows finding the appropriate
 * transformer for a given type.
 */
final class TransformerCollection
{
    /** @var array<class-string, Transformer> */
    private array $transformers = [];

    /**
     * Add a transformer for a specific type.
     *
     * @param  class-string  $type
     */
    public function add(string $type, Transformer $transformer): self
    {
        $this->transformers[$type] = $transformer;

        return $this;
    }

    /**
     * Find a transformer for the given type.
     *
     * @param  class-string  $type
     */
    public function findForType(string $type): ?Transformer
    {
        // Direct match
        if (isset($this->transformers[$type])) {
            return $this->transformers[$type];
        }

        // Check inheritance
        foreach ($this->transformers as $registeredType => $transformer) {
            if (is_a($type, $registeredType, true)) {
                return $transformer;
            }
        }

        // Check if transformer supports the type
        foreach ($this->transformers as $transformer) {
            if ($transformer->supports($type)) {
                return $transformer;
            }
        }

        return null;
    }

    /**
     * Check if a transformer exists for the type.
     *
     * @param  class-string  $type
     */
    public function has(string $type): bool
    {
        return $this->findForType($type) !== null;
    }

    /**
     * Merge with another collection.
     */
    public function merge(self $other): self
    {
        $collection = new self;

        foreach ($this->transformers as $type => $transformer) {
            $collection->add($type, $transformer);
        }

        foreach ($other->transformers as $type => $transformer) {
            $collection->add($type, $transformer);
        }

        return $collection;
    }

    /**
     * Get all transformers.
     *
     * @return array<class-string, Transformer>
     */
    public function all(): array
    {
        return $this->transformers;
    }
}
