<?php

declare(strict_types=1);

use OybekDaniyarov\LaravelTrpc\Collections\RouteCollection;
use OybekDaniyarov\LaravelTrpc\Data\GeneratorResult;
use OybekDaniyarov\LaravelTrpc\Data\PipelinePayload;
use OybekDaniyarov\LaravelTrpc\Data\RouteData;
use OybekDaniyarov\LaravelTrpc\TrpcConfig;

it('creates with static constructor', function () {
    $config = new TrpcConfig([
        'api_prefix' => 'api',
    ]);

    $payload = PipelinePayload::create($config);

    expect($payload)->toBeInstanceOf(PipelinePayload::class)
        ->and($payload->config)->toBe($config)
        ->and($payload->routes)->toBeInstanceOf(RouteCollection::class)
        ->and($payload->routes->count())->toBe(0)
        ->and($payload->result)->toBeInstanceOf(GeneratorResult::class)
        ->and($payload->result->isEmpty())->toBeTrue();
});

it('withMetadata adds metadata', function () {
    $config = new TrpcConfig([]);
    $payload = PipelinePayload::create($config);

    $result = $payload->withMetadata('key', 'value');

    expect($result)->toBe($payload) // Should return same instance (fluent)
        ->and($payload->metadata)->toHaveKey('key')
        ->and($payload->metadata['key'])->toBe('value');
});

it('getMetadata retrieves metadata', function () {
    $config = new TrpcConfig([]);
    $payload = PipelinePayload::create($config);
    $payload->withMetadata('testKey', 'testValue');

    expect($payload->getMetadata('testKey'))->toBe('testValue');
});

it('getMetadata returns default for missing key', function () {
    $config = new TrpcConfig([]);
    $payload = PipelinePayload::create($config);

    expect($payload->getMetadata('nonexistent'))->toBeNull()
        ->and($payload->getMetadata('nonexistent', 'default'))->toBe('default');
});

it('constructor initializes with provided values', function () {
    $config = new TrpcConfig([]);
    $routes = new RouteCollection([
        new RouteData(method: 'get', path: 'api/users', name: 'users.index', group: 'users'),
    ]);
    $result = new GeneratorResult(['test.ts' => 'content']);
    $metadata = ['key' => 'value'];

    $payload = new PipelinePayload(
        config: $config,
        routes: $routes,
        result: $result,
        metadata: $metadata,
    );

    expect($payload->config)->toBe($config)
        ->and($payload->routes)->toBe($routes)
        ->and($payload->result)->toBe($result)
        ->and($payload->metadata)->toBe($metadata);
});

it('routes can be modified', function () {
    $config = new TrpcConfig([]);
    $payload = PipelinePayload::create($config);

    $newRoutes = new RouteCollection([
        new RouteData(method: 'get', path: 'api/posts', name: 'posts.index', group: 'posts'),
    ]);

    $payload->routes = $newRoutes;

    expect($payload->routes)->toBe($newRoutes)
        ->and($payload->routes->count())->toBe(1);
});

it('result can be modified', function () {
    $config = new TrpcConfig([]);
    $payload = PipelinePayload::create($config);

    $newResult = new GeneratorResult(['file.ts' => 'content']);

    $payload->result = $newResult;

    expect($payload->result)->toBe($newResult)
        ->and($payload->result->isEmpty())->toBeFalse();
});

it('supports chaining withMetadata calls', function () {
    $config = new TrpcConfig([]);
    $payload = PipelinePayload::create($config)
        ->withMetadata('key1', 'value1')
        ->withMetadata('key2', 'value2')
        ->withMetadata('key3', 'value3');

    expect($payload->getMetadata('key1'))->toBe('value1')
        ->and($payload->getMetadata('key2'))->toBe('value2')
        ->and($payload->getMetadata('key3'))->toBe('value3');
});

it('metadata can store complex values', function () {
    $config = new TrpcConfig([]);
    $payload = PipelinePayload::create($config);

    $payload->withMetadata('array', ['a', 'b', 'c']);
    $payload->withMetadata('object', (object) ['key' => 'value']);
    $payload->withMetadata('closure', fn () => 'test');

    expect($payload->getMetadata('array'))->toBe(['a', 'b', 'c'])
        ->and($payload->getMetadata('object'))->toBeObject()
        ->and($payload->getMetadata('closure'))->toBeCallable();
});

it('config is readonly', function () {
    $config = new TrpcConfig(['api_prefix' => 'api']);
    $payload = PipelinePayload::create($config);

    expect($payload->config->getApiPrefix())->toBe('api');
});
