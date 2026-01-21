<?php

declare(strict_types=1);

use OybekDaniyarov\LaravelTrpc\Collections\RouteCollection;
use OybekDaniyarov\LaravelTrpc\Data\GeneratorResult;
use OybekDaniyarov\LaravelTrpc\Data\PipelinePayload;
use OybekDaniyarov\LaravelTrpc\Data\RouteData;
use OybekDaniyarov\LaravelTrpc\TrpcConfig;

// Note: CollectRoutesPipe depends on DefaultRouteCollector which requires RouteTypeExtractor
// with complex Surveyor/Ranger bindings. These tests focus on PipelinePayload behavior.

it('PipelinePayload can be created with config', function () {
    $config = new TrpcConfig([
        'api_prefix' => 'api',
        'exclude_methods' => ['options', 'head'],
    ]);

    $payload = PipelinePayload::create($config);

    expect($payload)->toBeInstanceOf(PipelinePayload::class)
        ->and($payload->routes)->toBeInstanceOf(RouteCollection::class)
        ->and($payload->config)->toBe($config);
});

it('PipelinePayload routes can be updated', function () {
    $config = new TrpcConfig([
        'api_prefix' => 'api',
    ]);

    $payload = PipelinePayload::create($config);

    $routes = new RouteCollection([
        new RouteData(method: 'get', path: 'api/users', name: 'users.index', group: 'users'),
    ]);

    $payload->routes = $routes;

    expect($payload->routes->count())->toBe(1);
});

it('PipelinePayload result can be updated', function () {
    $config = new TrpcConfig([
        'api_prefix' => 'api',
    ]);

    $payload = PipelinePayload::create($config);

    $result = new GeneratorResult(['test.ts' => 'content']);
    $payload->result = $result;

    expect($payload->result->files)->toHaveKey('test.ts');
});

it('PipelinePayload supports metadata', function () {
    $config = new TrpcConfig([
        'api_prefix' => 'api',
    ]);

    $payload = PipelinePayload::create($config)
        ->withMetadata('key1', 'value1')
        ->withMetadata('key2', 'value2');

    expect($payload->getMetadata('key1'))->toBe('value1')
        ->and($payload->getMetadata('key2'))->toBe('value2')
        ->and($payload->getMetadata('nonexistent', 'default'))->toBe('default');
});

it('RouteCollection can be sorted by name', function () {
    $routes = new RouteCollection([
        new RouteData(method: 'get', path: 'api/posts', name: 'posts.index', group: 'posts'),
        new RouteData(method: 'get', path: 'api/users', name: 'users.index', group: 'users'),
        new RouteData(method: 'get', path: 'api/auth', name: 'auth.login', group: 'auth'),
    ]);

    $sorted = $routes->sortByName();
    $names = $sorted->map(fn (RouteData $r) => $r->name);

    expect($names)->toBe(['auth.login', 'posts.index', 'users.index']);
});

it('RouteCollection can merge collections', function () {
    $routes1 = new RouteCollection([
        new RouteData(method: 'get', path: 'api/users', name: 'users.index', group: 'users'),
    ]);

    $routes2 = new RouteCollection([
        new RouteData(method: 'get', path: 'api/posts', name: 'posts.index', group: 'posts'),
    ]);

    $merged = $routes1->merge($routes2);

    expect($merged->count())->toBe(2)
        ->and($merged->has('users.index'))->toBeTrue()
        ->and($merged->has('posts.index'))->toBeTrue();
});
