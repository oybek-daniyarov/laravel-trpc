<?php

declare(strict_types=1);

namespace OybekDaniyarov\LaravelTrpc\Pipes;

use Closure;
use OybekDaniyarov\LaravelTrpc\Collections\GeneratorCollection;
use OybekDaniyarov\LaravelTrpc\Contracts\Generator;
use OybekDaniyarov\LaravelTrpc\Contracts\Pipe;
use OybekDaniyarov\LaravelTrpc\Data\Context\GeneratorContext;
use OybekDaniyarov\LaravelTrpc\Data\GeneratorResult;
use OybekDaniyarov\LaravelTrpc\Data\PipelinePayload;

/**
 * Pipe that generates output using registered generators.
 */
final class GenerateOutputPipe implements Pipe
{
    public function handle(PipelinePayload $payload, Closure $next): PipelinePayload
    {
        $generators = $this->buildGeneratorCollection($payload);
        $enabledGenerators = $payload->getMetadata('enabledGenerators', ['typescript']);

        $result = new GeneratorResult([]);

        foreach ($enabledGenerators as $name) {
            if (! $generators->has($name)) {
                continue;
            }

            $generator = $generators->get($name);
            $context = $this->buildContext($payload, $name);
            $generatorResult = $generator->generate($payload->routes, $context);
            $result = $result->merge($generatorResult);
        }

        $payload->result = $result;

        return $next($payload);
    }

    private function buildGeneratorCollection(PipelinePayload $payload): GeneratorCollection
    {
        $collection = new GeneratorCollection;

        foreach ($payload->config->getGenerators() as $name => $generatorClass) {
            /** @var Generator $generator */
            $generator = app($generatorClass, ['config' => $payload->config]);
            $collection->add($name, $generator);
        }

        return $collection;
    }

    private function buildContext(PipelinePayload $payload, string $generatorName): GeneratorContext
    {
        $isPostman = $generatorName === 'postman';

        return new GeneratorContext(
            outputPath: $isPostman
                ? $payload->config->getPostmanOutputPath()
                : $payload->config->getOutputPath(),
            config: $payload->config,
            postman: $isPostman,
            postmanEnv: $isPostman && ($payload->getMetadata('postmanEnv', false)),
        );
    }
}
