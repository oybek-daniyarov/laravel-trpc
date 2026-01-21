<?php

declare(strict_types=1);

use OybekDaniyarov\LaravelTrpc\Data\Context\TransformContext;
use OybekDaniyarov\LaravelTrpc\Data\RouteData;
use OybekDaniyarov\LaravelTrpc\TrpcConfig;

it('creates with default values', function () {
    $context = new TransformContext;

    expect($context->route)->toBeNull()
        ->and($context->config)->toBeNull()
        ->and($context->metadata)->toBe([]);
});

it('creates with all parameters', function () {
    $route = new RouteData(
        method: 'get',
        path: 'api/users',
        name: 'users.index',
        group: 'users',
    );

    $config = new TrpcConfig([]);

    $context = new TransformContext(
        route: $route,
        config: $config,
        metadata: ['key' => 'value'],
    );

    expect($context->route)->toBe($route)
        ->and($context->config)->toBe($config)
        ->and($context->metadata)->toBe(['key' => 'value']);
});

it('withRoute creates new context with route', function () {
    $originalContext = new TransformContext(
        config: new TrpcConfig([]),
        metadata: ['existing' => 'data'],
    );

    $route = new RouteData(
        method: 'post',
        path: 'api/users',
        name: 'users.store',
        group: 'users',
    );

    $newContext = $originalContext->withRoute($route);

    expect($newContext)->not->toBe($originalContext)
        ->and($newContext->route)->toBe($route)
        ->and($newContext->config)->toBe($originalContext->config)
        ->and($newContext->metadata)->toBe($originalContext->metadata);
});

it('withMetadata creates new context with metadata', function () {
    $originalContext = new TransformContext(
        config: new TrpcConfig([]),
        metadata: ['existing' => 'data'],
    );

    $newContext = $originalContext->withMetadata('newKey', 'newValue');

    expect($newContext)->not->toBe($originalContext)
        ->and($newContext->metadata)->toHaveKey('existing')
        ->and($newContext->metadata)->toHaveKey('newKey')
        ->and($newContext->metadata['newKey'])->toBe('newValue');
});

it('is readonly', function () {
    $route = new RouteData(
        method: 'get',
        path: 'api/users',
        name: 'users.index',
        group: 'users',
    );

    $context = new TransformContext(route: $route);

    // These should be accessible
    expect($context->route)->toBe($route)
        ->and($context->config)->toBeNull()
        ->and($context->metadata)->toBe([]);
});

it('withRoute preserves config and metadata', function () {
    $config = new TrpcConfig(['api_prefix' => 'api']);
    $originalRoute = new RouteData(
        method: 'get',
        path: 'api/users',
        name: 'users.index',
        group: 'users',
    );

    $context = new TransformContext(
        route: $originalRoute,
        config: $config,
        metadata: ['key1' => 'value1'],
    );

    $newRoute = new RouteData(
        method: 'post',
        path: 'api/posts',
        name: 'posts.store',
        group: 'posts',
    );

    $newContext = $context->withRoute($newRoute);

    expect($newContext->route)->toBe($newRoute)
        ->and($newContext->config)->toBe($config)
        ->and($newContext->metadata)->toBe(['key1' => 'value1']);
});

it('withMetadata merges with existing metadata', function () {
    $context = new TransformContext(
        metadata: ['key1' => 'value1', 'key2' => 'value2'],
    );

    $newContext = $context->withMetadata('key3', 'value3');

    expect($newContext->metadata)->toBe([
        'key1' => 'value1',
        'key2' => 'value2',
        'key3' => 'value3',
    ]);
});

it('withMetadata can override existing metadata key', function () {
    $context = new TransformContext(
        metadata: ['key' => 'original'],
    );

    $newContext = $context->withMetadata('key', 'updated');

    expect($newContext->metadata['key'])->toBe('updated');
});
