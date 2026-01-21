<?php

declare(strict_types=1);

use OybekDaniyarov\LaravelTrpc\Collections\RouteCollection;
use OybekDaniyarov\LaravelTrpc\TrpcConfig;

// Note: DefaultRouteCollector depends on RouteTypeExtractor which requires Laravel Surveyor
// and Ranger packages with complex bindings. These unit tests focus on the config behavior
// and basic collection functionality without testing the full collector.

it('TrpcConfig returns correct api prefix', function () {
    $config = new TrpcConfig([
        'api_prefix' => 'api/v1',
    ]);

    expect($config->getApiPrefix())->toBe('api/v1');
});

it('TrpcConfig returns correct exclude methods', function () {
    $config = new TrpcConfig([
        'exclude_methods' => ['options', 'head', 'trace'],
    ]);

    expect($config->getExcludeMethods())->toBe(['options', 'head', 'trace']);
});

it('TrpcConfig returns default exclude methods when not configured', function () {
    $config = new TrpcConfig([]);

    expect($config->getExcludeMethods())->toBe(['options', 'head']);
});

it('TrpcConfig returns route groups', function () {
    $config = new TrpcConfig([
        'route_groups' => [
            'auth' => ['login', 'register', 'logout'],
            'users' => ['users', 'profile'],
        ],
    ]);

    $groups = $config->getRouteGroups();

    expect($groups)->toHaveKey('auth')
        ->and($groups)->toHaveKey('users')
        ->and($groups['auth'])->toContain('login')
        ->and($groups['users'])->toContain('users');
});

it('TrpcConfig returns route name mappings', function () {
    $config = new TrpcConfig([
        'route_name_mappings' => [
            'api/custom-route' => 'custom.mapped.name',
        ],
    ]);

    $mappings = $config->getRouteNameMappings();

    expect($mappings)->toHaveKey('api/custom-route')
        ->and($mappings['api/custom-route'])->toBe('custom.mapped.name');
});

it('TrpcConfig returns empty route name mappings by default', function () {
    $config = new TrpcConfig([]);

    expect($config->getRouteNameMappings())->toBe([]);
});

it('TrpcConfig returns default api prefix', function () {
    $config = new TrpcConfig([]);

    expect($config->getApiPrefix())->toBe('api');
});

it('TrpcConfig returns collectors', function () {
    $config = new TrpcConfig([
        'collectors' => [
            'App\\Custom\\Collector',
        ],
    ]);

    $collectors = $config->getCollectors();

    expect($collectors)->toContain('App\\Custom\\Collector');
});

it('RouteCollection can be created empty', function () {
    $collection = new RouteCollection;

    expect($collection->count())->toBe(0);
});

it('RouteCollection can be filtered', function () {
    $collection = new RouteCollection([
        new OybekDaniyarov\LaravelTrpc\Data\RouteData(
            method: 'get',
            path: 'api/users',
            name: 'users.index',
            group: 'users'
        ),
        new OybekDaniyarov\LaravelTrpc\Data\RouteData(
            method: 'post',
            path: 'api/users',
            name: 'users.store',
            group: 'users'
        ),
    ]);

    $filtered = $collection->filter(fn ($r) => $r->method === 'get');

    expect($filtered->count())->toBe(1);
});

// Note: Tests requiring Route facade and DefaultRouteCollector moved to Feature tests
// since they need full Laravel app context with Surveyor/Ranger dependencies.
