<?php

declare(strict_types=1);

namespace OybekDaniyarov\LaravelTrpc;

use Illuminate\Pipeline\Pipeline;
use OybekDaniyarov\LaravelTrpc\Contracts\Pipe;
use OybekDaniyarov\LaravelTrpc\Data\GeneratorResult;
use OybekDaniyarov\LaravelTrpc\Data\PipelinePayload;
use OybekDaniyarov\LaravelTrpc\Pipes\CollectRoutesPipe;
use OybekDaniyarov\LaravelTrpc\Pipes\ExtractTypesPipe;
use OybekDaniyarov\LaravelTrpc\Pipes\GenerateOutputPipe;
use OybekDaniyarov\LaravelTrpc\Pipes\TransformTypesPipe;

/**
 * Pipeline for processing route collection and generation.
 *
 * Provides a fluent interface for customizing the generation process
 * by adding, replacing, or removing pipes.
 */
final class TrpcPipeline
{
    /** @var array<int, class-string<Pipe>|Pipe> */
    private array $pipes = [];

    /** @var array<class-string<Pipe>, class-string<Pipe>> */
    private array $replacements = [];

    private function __construct()
    {
        // Default pipeline
        $this->pipes = [
            CollectRoutesPipe::class,
            ExtractTypesPipe::class,
            TransformTypesPipe::class,
            GenerateOutputPipe::class,
        ];
    }

    /**
     * Create a new pipeline instance.
     */
    public static function create(): self
    {
        return new self;
    }

    /**
     * Add a pipe to the end of the pipeline.
     *
     * @param  class-string<Pipe>|Pipe  $pipe
     */
    public function through(string|Pipe $pipe): self
    {
        $this->pipes[] = $pipe;

        return $this;
    }

    /**
     * Add a pipe to the beginning of the pipeline.
     *
     * @param  class-string<Pipe>|Pipe  $pipe
     */
    public function firstThrough(string|Pipe $pipe): self
    {
        array_unshift($this->pipes, $pipe);

        return $this;
    }

    /**
     * Add a pipe before another pipe.
     *
     * @param  class-string<Pipe>  $before
     * @param  class-string<Pipe>|Pipe  $pipe
     */
    public function before(string $before, string|Pipe $pipe): self
    {
        $index = $this->findPipeIndex($before);

        if ($index !== false) {
            array_splice($this->pipes, $index, 0, [$pipe]);
        }

        return $this;
    }

    /**
     * Add a pipe after another pipe.
     *
     * @param  class-string<Pipe>  $after
     * @param  class-string<Pipe>|Pipe  $pipe
     */
    public function after(string $after, string|Pipe $pipe): self
    {
        $index = $this->findPipeIndex($after);

        if ($index !== false) {
            array_splice($this->pipes, $index + 1, 0, [$pipe]);
        }

        return $this;
    }

    /**
     * Replace a pipe with another.
     *
     * @param  class-string<Pipe>  $pipe
     * @param  class-string<Pipe>  $replacement
     */
    public function replace(string $pipe, string $replacement): self
    {
        $this->replacements[$pipe] = $replacement;

        return $this;
    }

    /**
     * Remove a pipe from the pipeline.
     *
     * @param  class-string<Pipe>  $pipe
     */
    public function without(string $pipe): self
    {
        $this->pipes = array_filter(
            $this->pipes,
            fn ($p) => ! ($p === $pipe || (is_string($p) && is_a($p, $pipe, true)))
        );

        return $this;
    }

    /**
     * Process the pipeline with the given config.
     */
    public function process(TrpcConfig $config): GeneratorResult
    {
        $payload = PipelinePayload::create($config);
        $pipes = $this->resolvePipes();

        $result = app(Pipeline::class)
            ->send($payload)
            ->through($pipes)
            ->then(fn (PipelinePayload $p) => $p);

        return $result->result;
    }

    /**
     * Get the resolved pipes with replacements applied.
     *
     * @return array<int, class-string<Pipe>|Pipe>
     */
    private function resolvePipes(): array
    {
        return array_map(function ($pipe) {
            if (is_string($pipe) && isset($this->replacements[$pipe])) {
                return $this->replacements[$pipe];
            }

            return $pipe;
        }, array_values($this->pipes));
    }

    /**
     * Find the index of a pipe in the pipeline.
     *
     * @param  class-string<Pipe>  $pipe
     */
    private function findPipeIndex(string $pipe): int|false
    {
        foreach ($this->pipes as $index => $p) {
            if ($p === $pipe || (is_string($p) && is_a($p, $pipe, true))) {
                return $index;
            }
        }

        return false;
    }
}
