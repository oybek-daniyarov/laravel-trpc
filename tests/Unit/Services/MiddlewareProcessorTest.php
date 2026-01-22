<?php

declare(strict_types=1);

use OybekDaniyarov\LaravelTrpc\Services\MiddlewareProcessor;
use OybekDaniyarov\LaravelTrpc\TrpcConfig;

it('returns empty array when input is empty', function () {
    $config = new TrpcConfig([
        'middleware' => [
            'exclude' => [],
            'short_names' => true,
        ],
    ]);
    $processor = new MiddlewareProcessor($config);

    expect($processor->process([]))->toBe([]);
});

it('passes through middleware when no exclusions configured', function () {
    $config = new TrpcConfig([
        'middleware' => [
            'exclude' => [],
            'short_names' => false,
        ],
    ]);
    $processor = new MiddlewareProcessor($config);

    $middleware = ['api', 'auth:sanctum', 'web'];

    expect($processor->process($middleware))->toBe(['api', 'auth:sanctum', 'web']);
});

it('excludes middleware matching exact pattern', function () {
    $config = new TrpcConfig([
        'middleware' => [
            'exclude' => ['Stancl\\Tenancy\\Middleware\\InitializeTenancyByDomain'],
            'short_names' => false,
        ],
    ]);
    $processor = new MiddlewareProcessor($config);

    $middleware = [
        'api',
        'Stancl\\Tenancy\\Middleware\\InitializeTenancyByDomain',
        'auth:sanctum',
    ];

    expect($processor->process($middleware))->toBe(['api', 'auth:sanctum']);
});

it('excludes middleware matching wildcard pattern', function () {
    $config = new TrpcConfig([
        'middleware' => [
            'exclude' => ['Stancl\\Tenancy\\*'],
            'short_names' => false,
        ],
    ]);
    $processor = new MiddlewareProcessor($config);

    $middleware = [
        'api',
        'Stancl\\Tenancy\\Middleware\\InitializeTenancyByDomain',
        'Stancl\\Tenancy\\Middleware\\PreventAccessFromCentralDomains',
        'auth:sanctum',
    ];

    expect($processor->process($middleware))->toBe(['api', 'auth:sanctum']);
});

it('excludes middleware matching multiple patterns', function () {
    $config = new TrpcConfig([
        'middleware' => [
            'exclude' => [
                'Stancl\\Tenancy\\*',
                'App\\Http\\Middleware\\TrustProxies',
            ],
            'short_names' => false,
        ],
    ]);
    $processor = new MiddlewareProcessor($config);

    $middleware = [
        'api',
        'App\\Http\\Middleware\\TrustProxies',
        'Stancl\\Tenancy\\Middleware\\InitializeTenancyByDomain',
        'auth:sanctum',
    ];

    expect($processor->process($middleware))->toBe(['api', 'auth:sanctum']);
});

it('transforms FQCN to short name when enabled', function () {
    $config = new TrpcConfig([
        'middleware' => [
            'exclude' => [],
            'short_names' => true,
        ],
    ]);
    $processor = new MiddlewareProcessor($config);

    $middleware = [
        'api',
        'Stancl\\Tenancy\\Middleware\\InitializeTenancyByDomain',
        'auth:sanctum',
    ];

    expect($processor->process($middleware))->toBe([
        'api',
        'InitializeTenancyByDomain',
        'auth:sanctum',
    ]);
});

it('preserves parameters when transforming to short name', function () {
    $config = new TrpcConfig([
        'middleware' => [
            'exclude' => [],
            'short_names' => true,
        ],
    ]);
    $processor = new MiddlewareProcessor($config);

    $middleware = [
        'App\\Http\\Middleware\\RateLimiter:api',
        'App\\Http\\Middleware\\Authenticate:sanctum',
    ];

    expect($processor->process($middleware))->toBe([
        'RateLimiter:api',
        'Authenticate:sanctum',
    ]);
});

it('does not transform simple middleware names', function () {
    $config = new TrpcConfig([
        'middleware' => [
            'exclude' => [],
            'short_names' => true,
        ],
    ]);
    $processor = new MiddlewareProcessor($config);

    $middleware = ['api', 'web', 'auth:sanctum', 'verified'];

    expect($processor->process($middleware))->toBe(['api', 'web', 'auth:sanctum', 'verified']);
});

it('applies both exclusion and short names transformation', function () {
    $config = new TrpcConfig([
        'middleware' => [
            'exclude' => ['Stancl\\Tenancy\\*'],
            'short_names' => true,
        ],
    ]);
    $processor = new MiddlewareProcessor($config);

    $middleware = [
        'api',
        'Stancl\\Tenancy\\Middleware\\InitializeTenancyByDomain',
        'App\\Http\\Middleware\\Authenticate:sanctum',
        'web',
    ];

    expect($processor->process($middleware))->toBe([
        'api',
        'Authenticate:sanctum',
        'web',
    ]);
});

it('reindexes array after filtering', function () {
    $config = new TrpcConfig([
        'middleware' => [
            'exclude' => ['excluded'],
            'short_names' => false,
        ],
    ]);
    $processor = new MiddlewareProcessor($config);

    $middleware = ['first', 'excluded', 'third'];
    $result = $processor->process($middleware);

    // Should be sequential keys [0, 1], not [0, 2]
    expect(array_keys($result))->toBe([0, 1])
        ->and($result)->toBe(['first', 'third']);
});

it('uses default config values when middleware config not set', function () {
    $config = new TrpcConfig([]);
    $processor = new MiddlewareProcessor($config);

    $middleware = [
        'api',
        'Stancl\\Tenancy\\Middleware\\InitializeTenancyByDomain',
        'auth:sanctum',
    ];

    // Default: short_names = true, exclude = []
    expect($processor->process($middleware))->toBe([
        'api',
        'InitializeTenancyByDomain',
        'auth:sanctum',
    ]);
});

it('handles middleware with multiple colons in parameters', function () {
    $config = new TrpcConfig([
        'middleware' => [
            'exclude' => [],
            'short_names' => true,
        ],
    ]);
    $processor = new MiddlewareProcessor($config);

    $middleware = ['App\\Http\\Middleware\\Custom:param1:param2'];

    expect($processor->process($middleware))->toBe(['Custom:param1:param2']);
});

// Test toShortName method directly

it('converts FQCN to short name', function () {
    $config = new TrpcConfig([]);
    $processor = new MiddlewareProcessor($config);

    expect($processor->toShortName('Stancl\\Tenancy\\Middleware\\InitializeTenancyByDomain'))
        ->toBe('InitializeTenancyByDomain');
});

it('keeps short middleware names unchanged', function () {
    $config = new TrpcConfig([]);
    $processor = new MiddlewareProcessor($config);

    expect($processor->toShortName('auth:sanctum'))->toBe('auth:sanctum')
        ->and($processor->toShortName('web'))->toBe('web')
        ->and($processor->toShortName('api'))->toBe('api');
});

it('preserves parameters in FQCN middleware', function () {
    $config = new TrpcConfig([]);
    $processor = new MiddlewareProcessor($config);

    expect($processor->toShortName('App\\Http\\Middleware\\RateLimiter:api'))
        ->toBe('RateLimiter:api');
});
