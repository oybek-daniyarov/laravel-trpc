<?php

declare(strict_types=1);

use OybekDaniyarov\LaravelTrpc\Collections\RouteCollection;
use OybekDaniyarov\LaravelTrpc\Collections\TransformerCollection;
use OybekDaniyarov\LaravelTrpc\Data\GeneratorResult;
use OybekDaniyarov\LaravelTrpc\Data\PipelinePayload;
use OybekDaniyarov\LaravelTrpc\Data\RouteData;
use OybekDaniyarov\LaravelTrpc\Pipes\TransformTypesPipe;
use OybekDaniyarov\LaravelTrpc\Transformers\CarbonTransformer;
use OybekDaniyarov\LaravelTrpc\Transformers\DataTransformer;
use OybekDaniyarov\LaravelTrpc\Transformers\EnumTransformer;
use OybekDaniyarov\LaravelTrpc\TrpcConfig;
use Spatie\LaravelData\Data;

it('builds transformer collection from config', function () {
    $config = new TrpcConfig([
        'api_prefix' => 'api',
        'transformers' => [
            Data::class => DataTransformer::class,
            BackedEnum::class => EnumTransformer::class,
        ],
    ]);

    $payload = new PipelinePayload(
        config: $config,
        routes: new RouteCollection,
        result: new GeneratorResult([]),
    );

    $pipe = new TransformTypesPipe;

    $result = $pipe->handle($payload, fn ($p) => $p);

    expect($result->getMetadata('transformers'))->toBeInstanceOf(TransformerCollection::class);
});

it('passes payload to next pipe', function () {
    $config = new TrpcConfig([
        'api_prefix' => 'api',
        'transformers' => [],
    ]);

    $payload = new PipelinePayload(
        config: $config,
        routes: new RouteCollection,
        result: new GeneratorResult([]),
    );

    $pipe = new TransformTypesPipe;
    $nextCalled = false;

    $pipe->handle($payload, function ($p) use (&$nextCalled) {
        $nextCalled = true;

        return $p;
    });

    expect($nextCalled)->toBeTrue();
});

it('stores transformers in metadata', function () {
    $config = new TrpcConfig([
        'api_prefix' => 'api',
        'transformers' => [
            Carbon\Carbon::class => CarbonTransformer::class,
        ],
    ]);

    $payload = new PipelinePayload(
        config: $config,
        routes: new RouteCollection,
        result: new GeneratorResult([]),
    );

    $pipe = new TransformTypesPipe;

    $result = $pipe->handle($payload, fn ($p) => $p);

    expect($result->metadata)->toHaveKey('transformers');
});

it('handles empty transformers config', function () {
    $config = new TrpcConfig([
        'api_prefix' => 'api',
        'transformers' => [],
    ]);

    $payload = new PipelinePayload(
        config: $config,
        routes: new RouteCollection,
        result: new GeneratorResult([]),
    );

    $pipe = new TransformTypesPipe;

    $result = $pipe->handle($payload, fn ($p) => $p);

    $transformers = $result->getMetadata('transformers');
    expect($transformers)->toBeInstanceOf(TransformerCollection::class);
});

it('preserves existing payload data', function () {
    $config = new TrpcConfig([
        'api_prefix' => 'api',
        'transformers' => [],
    ]);

    $routes = new RouteCollection([
        new RouteData(method: 'get', path: 'api/users', name: 'users.index', group: 'users'),
    ]);

    $payload = new PipelinePayload(
        config: $config,
        routes: $routes,
        result: new GeneratorResult([]),
        metadata: ['existingKey' => 'existingValue'],
    );

    $pipe = new TransformTypesPipe;

    $result = $pipe->handle($payload, fn ($p) => $p);

    expect($result->routes)->toBe($routes)
        ->and($result->getMetadata('existingKey'))->toBe('existingValue');
});

it('registers multiple transformers', function () {
    $config = new TrpcConfig([
        'api_prefix' => 'api',
        'transformers' => [
            Data::class => DataTransformer::class,
            BackedEnum::class => EnumTransformer::class,
            Carbon\Carbon::class => CarbonTransformer::class,
        ],
    ]);

    $payload = new PipelinePayload(
        config: $config,
        routes: new RouteCollection,
        result: new GeneratorResult([]),
    );

    $pipe = new TransformTypesPipe;

    $result = $pipe->handle($payload, fn ($p) => $p);

    $transformers = $result->getMetadata('transformers');
    expect($transformers)->toBeInstanceOf(TransformerCollection::class);
});
