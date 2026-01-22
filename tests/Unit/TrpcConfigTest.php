<?php

declare(strict_types=1);

use InvalidArgumentException;
use OybekDaniyarov\LaravelTrpc\TrpcConfig;

// Note: Tests that call getOutputPath() or getPostmanOutputPath() with empty config
// are in Feature tests because those methods call resource_path()/storage_path()
// as defaults, which require full Laravel app context.

it('can be created with config array', function () {
    $config = new TrpcConfig([
        'api_prefix' => 'api/v2',
    ]);

    expect($config->getApiPrefix())->toBe('api/v2');
});

it('returns default values when config is empty', function () {
    $config = new TrpcConfig;

    expect($config->getApiPrefix())->toBe('api')
        ->and($config->getExcludeMethods())->toBe(['options', 'head']);
});

it('can get nested config values', function () {
    $config = new TrpcConfig([
        'postman' => [
            'collection_name' => 'My API Collection',
            'auth_type' => 'apikey',
        ],
    ]);

    expect($config->get('postman.collection_name'))->toBe('My API Collection')
        ->and($config->get('postman.auth_type'))->toBe('apikey');
});

it('returns default for missing nested keys', function () {
    $config = new TrpcConfig;

    expect($config->get('postman.nonexistent', 'default'))->toBe('default');
});

it('caches config values', function () {
    $config = new TrpcConfig([
        'api_prefix' => 'api/v1',
    ]);

    // First call
    $value1 = $config->getApiPrefix();

    // Second call should use cache
    $value2 = $config->getApiPrefix();

    expect($value1)->toBe($value2)
        ->and($value1)->toBe('api/v1');
});

it('returns all config values', function () {
    $configArray = [
        'api_prefix' => 'api',
    ];

    $config = new TrpcConfig($configArray);

    expect($config->all())->toBe($configArray);
});

it('returns route groups', function () {
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

it('returns empty route groups by default', function () {
    $config = new TrpcConfig;

    expect($config->getRouteGroups())->toBe([]);
});

it('returns route name mappings', function () {
    $config = new TrpcConfig([
        'route_name_mappings' => [
            'api/custom-route' => 'custom.mapped.name',
        ],
    ]);

    $mappings = $config->getRouteNameMappings();

    expect($mappings)->toHaveKey('api/custom-route')
        ->and($mappings['api/custom-route'])->toBe('custom.mapped.name');
});

it('returns empty route name mappings by default', function () {
    $config = new TrpcConfig;

    expect($config->getRouteNameMappings())->toBe([]);
});

it('returns transformers', function () {
    $config = new TrpcConfig([
        'transformers' => [
            'CustomType' => 'CustomTransformer',
        ],
    ]);

    $transformers = $config->getTransformers();

    expect($transformers)->toHaveKey('CustomType')
        ->and($transformers['CustomType'])->toBe('CustomTransformer');
});

it('returns empty transformers by default', function () {
    $config = new TrpcConfig;

    expect($config->getTransformers())->toBe([]);
});

it('returns version', function () {
    $config = new TrpcConfig([
        'version' => 'v2',
    ]);

    expect($config->getVersion())->toBe('v2');
});

it('returns default version', function () {
    $config = new TrpcConfig;

    expect($config->getVersion())->toBe('v1');
});

it('returns postman base url', function () {
    $config = new TrpcConfig([
        'postman' => [
            'base_url' => 'https://api.example.com',
        ],
    ]);

    expect($config->getPostmanBaseUrl())->toBe('https://api.example.com');
});

it('returns default postman base url', function () {
    $config = new TrpcConfig;

    expect($config->getPostmanBaseUrl())->toBe('{{base_url}}');
});

it('returns postman auth type', function () {
    $config = new TrpcConfig([
        'postman' => [
            'auth_type' => 'apikey',
        ],
    ]);

    expect($config->getPostmanAuthType())->toBe('apikey');
});

it('returns default postman auth type', function () {
    $config = new TrpcConfig;

    expect($config->getPostmanAuthType())->toBe('bearer');
});

it('returns postman default headers', function () {
    $config = new TrpcConfig([
        'postman' => [
            'default_headers' => [
                'X-Custom-Header' => 'value',
            ],
        ],
    ]);

    $headers = $config->getPostmanDefaultHeaders();

    expect($headers)->toHaveKey('X-Custom-Header')
        ->and($headers['X-Custom-Header'])->toBe('value');
});

it('returns empty postman default headers by default', function () {
    $config = new TrpcConfig;

    expect($config->getPostmanDefaultHeaders())->toBe([]);
});

// Validation tests
it('throws exception for invalid route_mode', function () {
    config(['trpc' => ['route_mode' => 'invalid_mode']]);

    expect(fn () => TrpcConfig::fromConfig())->toThrow(
        InvalidArgumentException::class,
        'trpc.route_mode must be one of'
    );
});

it('throws exception for invalid api_prefix type', function () {
    config(['trpc' => ['api_prefix' => 123]]);

    expect(fn () => TrpcConfig::fromConfig())->toThrow(
        InvalidArgumentException::class,
        'trpc.api_prefix must be a string'
    );
});

it('throws exception for non-array collectors', function () {
    config(['trpc' => ['collectors' => 'invalid']]);

    expect(fn () => TrpcConfig::fromConfig())->toThrow(
        InvalidArgumentException::class,
        'trpc.collectors must be an array'
    );
});

it('throws exception for invalid collector class', function () {
    config(['trpc' => ['collectors' => ['NonExistentClass']]]);

    expect(fn () => TrpcConfig::fromConfig())->toThrow(
        InvalidArgumentException::class,
        'trpc.collectors contains invalid class'
    );
});

it('throws exception for non-array generators', function () {
    config(['trpc' => ['generators' => 'invalid']]);

    expect(fn () => TrpcConfig::fromConfig())->toThrow(
        InvalidArgumentException::class,
        'trpc.generators must be an array'
    );
});

it('throws exception for invalid generator class', function () {
    config(['trpc' => ['generators' => ['typescript' => 'NonExistentClass']]]);

    expect(fn () => TrpcConfig::fromConfig())->toThrow(
        InvalidArgumentException::class,
        'trpc.generators.typescript contains invalid class'
    );
});

it('accepts valid configuration values', function () {
    config(['trpc' => [
        'route_mode' => 'api',
        'api_prefix' => 'api/v1',
        'collectors' => [OybekDaniyarov\LaravelTrpc\Collectors\DefaultRouteCollector::class],
        'generators' => [
            'typescript' => OybekDaniyarov\LaravelTrpc\Generators\TypeScriptGenerator::class,
        ],
    ]]);

    $config = TrpcConfig::fromConfig();

    expect($config->getRouteMode())->toBe('api')
        ->and($config->getApiPrefix())->toBe('api/v1');
});

// Preset and outputs tests
it('returns outputs directly when no preset is set', function () {
    $config = new TrpcConfig([
        'preset' => null,
        'outputs' => [
            'routes' => true,
            'grouped-api' => false,
        ],
    ]);

    $outputs = $config->getEffectiveOutputs();

    expect($outputs['routes'])->toBeTrue()
        ->and($outputs['grouped-api'])->toBeFalse();
});

it('preset includes grouped-api by default', function () {
    $config = new TrpcConfig([
        'preset' => 'inertia',
        'outputs' => [],
    ]);

    $outputs = $config->getEffectiveOutputs();

    expect($outputs['grouped-api'])->toBeTrue()
        ->and($outputs['inertia'])->toBeTrue();
});

it('user outputs override preset defaults', function () {
    $config = new TrpcConfig([
        'preset' => 'inertia',
        'outputs' => [
            'grouped-api' => false,
            'inertia' => false,
        ],
    ]);

    $outputs = $config->getEffectiveOutputs();

    // User's outputs should override preset defaults
    expect($outputs['grouped-api'])->toBeFalse()
        ->and($outputs['inertia'])->toBeFalse();
});

it('api preset enables react-query and queries', function () {
    $config = new TrpcConfig([
        'preset' => 'api',
        'outputs' => [],
    ]);

    $outputs = $config->getEffectiveOutputs();

    expect($outputs['react-query'])->toBeTrue()
        ->and($outputs['queries'])->toBeTrue();
});

it('spa preset enables both inertia and react-query', function () {
    $config = new TrpcConfig([
        'preset' => 'spa',
        'outputs' => [],
    ]);

    $outputs = $config->getEffectiveOutputs();

    expect($outputs['inertia'])->toBeTrue()
        ->and($outputs['react-query'])->toBeTrue()
        ->and($outputs['queries'])->toBeTrue();
});

// Middleware configuration tests

it('returns middleware exclude patterns', function () {
    $config = new TrpcConfig([
        'middleware' => [
            'exclude' => [
                'Stancl\\Tenancy\\*',
                'App\\Http\\Middleware\\TrustProxies',
            ],
        ],
    ]);

    $patterns = $config->getMiddlewareExcludePatterns();

    expect($patterns)->toBe([
        'Stancl\\Tenancy\\*',
        'App\\Http\\Middleware\\TrustProxies',
    ]);
});

it('returns empty middleware exclude patterns by default', function () {
    $config = new TrpcConfig;

    expect($config->getMiddlewareExcludePatterns())->toBe([]);
});

it('returns short middleware names setting', function () {
    $config = new TrpcConfig([
        'middleware' => [
            'short_names' => false,
        ],
    ]);

    expect($config->shouldUseShortMiddlewareNames())->toBeFalse();
});

it('returns true for short middleware names by default', function () {
    $config = new TrpcConfig;

    expect($config->shouldUseShortMiddlewareNames())->toBeTrue();
});
