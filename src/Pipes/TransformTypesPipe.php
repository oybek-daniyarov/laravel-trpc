<?php

declare(strict_types=1);

namespace OybekDaniyarov\LaravelTrpc\Pipes;

use Closure;
use OybekDaniyarov\LaravelTrpc\Collections\TransformerCollection;
use OybekDaniyarov\LaravelTrpc\Contracts\Pipe;
use OybekDaniyarov\LaravelTrpc\Contracts\Transformer;
use OybekDaniyarov\LaravelTrpc\Data\PipelinePayload;

/**
 * Pipe that applies type transformations to routes.
 *
 * Uses registered transformers to convert PHP types to TypeScript types.
 */
final class TransformTypesPipe implements Pipe
{
    public function handle(PipelinePayload $payload, Closure $next): PipelinePayload
    {
        // Build transformer collection from config
        $transformers = $this->buildTransformerCollection($payload);

        // Store for use by generators
        $payload->withMetadata('transformers', $transformers);

        return $next($payload);
    }

    private function buildTransformerCollection(PipelinePayload $payload): TransformerCollection
    {
        $collection = new TransformerCollection;

        foreach ($payload->config->getTransformers() as $type => $transformerClass) {
            /** @var Transformer $transformer */
            $transformer = app($transformerClass);
            $collection->add($type, $transformer);
        }

        return $collection;
    }
}
