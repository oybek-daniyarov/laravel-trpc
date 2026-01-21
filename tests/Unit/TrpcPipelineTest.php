<?php

declare(strict_types=1);

use OybekDaniyarov\LaravelTrpc\Contracts\Pipe;
use OybekDaniyarov\LaravelTrpc\Data\PipelinePayload;
use OybekDaniyarov\LaravelTrpc\Pipes\CollectRoutesPipe;
use OybekDaniyarov\LaravelTrpc\Pipes\ExtractTypesPipe;
use OybekDaniyarov\LaravelTrpc\Pipes\GenerateOutputPipe;
use OybekDaniyarov\LaravelTrpc\Pipes\TransformTypesPipe;
use OybekDaniyarov\LaravelTrpc\TrpcPipeline;

// Note: Tests that call process() are in Feature tests because they require
// a container instance for pipe resolution.

it('creates pipeline with default pipes', function () {
    $pipeline = TrpcPipeline::create();

    expect($pipeline)->toBeInstanceOf(TrpcPipeline::class);
});

it('adds pipe to end of pipeline with through()', function () {
    $customPipe = new class implements Pipe
    {
        public function handle(PipelinePayload $payload, Closure $next): PipelinePayload
        {
            $payload->withMetadata('custom', true);

            return $next($payload);
        }
    };

    $pipeline = TrpcPipeline::create()->through($customPipe);

    expect($pipeline)->toBeInstanceOf(TrpcPipeline::class);
});

it('adds pipe to beginning of pipeline with firstThrough()', function () {
    $customPipe = new class implements Pipe
    {
        public function handle(PipelinePayload $payload, Closure $next): PipelinePayload
        {
            $payload->withMetadata('first', true);

            return $next($payload);
        }
    };

    $pipeline = TrpcPipeline::create()->firstThrough($customPipe);

    expect($pipeline)->toBeInstanceOf(TrpcPipeline::class);
});

it('adds pipe before another with before()', function () {
    $customPipe = new class implements Pipe
    {
        public function handle(PipelinePayload $payload, Closure $next): PipelinePayload
        {
            return $next($payload);
        }
    };

    $pipeline = TrpcPipeline::create()
        ->before(GenerateOutputPipe::class, $customPipe);

    expect($pipeline)->toBeInstanceOf(TrpcPipeline::class);
});

it('adds pipe after another with after()', function () {
    $customPipe = new class implements Pipe
    {
        public function handle(PipelinePayload $payload, Closure $next): PipelinePayload
        {
            return $next($payload);
        }
    };

    $pipeline = TrpcPipeline::create()
        ->after(CollectRoutesPipe::class, $customPipe);

    expect($pipeline)->toBeInstanceOf(TrpcPipeline::class);
});

it('replaces pipe with another using replace()', function () {
    $replacementPipe = new class implements Pipe
    {
        public function handle(PipelinePayload $payload, Closure $next): PipelinePayload
        {
            $payload->withMetadata('replaced', true);

            return $next($payload);
        }
    };

    $pipeline = TrpcPipeline::create()
        ->replace(ExtractTypesPipe::class, get_class($replacementPipe));

    expect($pipeline)->toBeInstanceOf(TrpcPipeline::class);
});

it('removes pipe with without()', function () {
    $pipeline = TrpcPipeline::create()
        ->without(TransformTypesPipe::class);

    expect($pipeline)->toBeInstanceOf(TrpcPipeline::class);
});

it('supports fluent chaining', function () {
    $customPipe1 = new class implements Pipe
    {
        public function handle(PipelinePayload $payload, Closure $next): PipelinePayload
        {
            return $next($payload);
        }
    };

    $customPipe2 = new class implements Pipe
    {
        public function handle(PipelinePayload $payload, Closure $next): PipelinePayload
        {
            return $next($payload);
        }
    };

    $pipeline = TrpcPipeline::create()
        ->through($customPipe1)
        ->through($customPipe2)
        ->without(TransformTypesPipe::class);

    expect($pipeline)->toBeInstanceOf(TrpcPipeline::class);
});

it('handles pipe class strings', function () {
    $pipeline = TrpcPipeline::create()
        ->through(CollectRoutesPipe::class);

    expect($pipeline)->toBeInstanceOf(TrpcPipeline::class);
});
