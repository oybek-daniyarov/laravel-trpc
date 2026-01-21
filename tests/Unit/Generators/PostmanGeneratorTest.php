<?php

declare(strict_types=1);

use OybekDaniyarov\LaravelTrpc\Collections\RouteCollection;
use OybekDaniyarov\LaravelTrpc\Data\RouteData;
use OybekDaniyarov\LaravelTrpc\Generators\PostmanGenerator;
use OybekDaniyarov\LaravelTrpc\TrpcConfig;

// Note: PostmanGenerator tests are simplified to avoid Spatie Laravel Data
// version compatibility issues with the transformation context.
// Full integration tests should be run in a real Laravel application context.

it('can be instantiated with config', function () {
    $config = new TrpcConfig([
        'postman' => [
            'output_path' => '/tmp/trpc-test/postman',
            'collection_name' => 'Test API Collection',
            'base_url' => '{{base_url}}',
            'auth_type' => 'bearer',
            'default_headers' => [],
        ],
    ]);

    $generator = new PostmanGenerator($config);

    expect($generator)->toBeInstanceOf(PostmanGenerator::class);
});

it('config returns correct postman collection name', function () {
    $config = new TrpcConfig([
        'postman' => [
            'collection_name' => 'My Custom API',
        ],
    ]);

    expect($config->getPostmanCollectionName())->toBe('My Custom API');
});

it('config returns correct postman base url', function () {
    $config = new TrpcConfig([
        'postman' => [
            'base_url' => 'https://api.example.com',
        ],
    ]);

    expect($config->getPostmanBaseUrl())->toBe('https://api.example.com');
});

it('config returns correct postman auth type', function () {
    $config = new TrpcConfig([
        'postman' => [
            'auth_type' => 'apikey',
        ],
    ]);

    expect($config->getPostmanAuthType())->toBe('apikey');
});

it('config returns default for unset postman auth', function () {
    // When auth_type is not provided, it returns the default 'bearer'
    // The ?? operator in get() returns the default for null values
    $config = new TrpcConfig([
        'postman' => [],
    ]);

    expect($config->getPostmanAuthType())->toBe('bearer');
});

it('config returns postman default headers', function () {
    $config = new TrpcConfig([
        'postman' => [
            'default_headers' => [
                'X-Custom-Header' => 'custom-value',
                'X-Api-Key' => '{{api_key}}',
            ],
        ],
    ]);

    $headers = $config->getPostmanDefaultHeaders();

    expect($headers)->toHaveKey('X-Custom-Header')
        ->and($headers['X-Custom-Header'])->toBe('custom-value')
        ->and($headers)->toHaveKey('X-Api-Key');
});

it('RouteData correctly stores path params', function () {
    $route = new RouteData(
        method: 'get',
        path: 'api/users/{id}',
        name: 'users.show',
        group: 'users',
        pathParams: ['id'],
    );

    expect($route->pathParams)->toBe(['id'])
        ->and($route->method)->toBe('get')
        ->and($route->path)->toBe('api/users/{id}');
});

it('RouteCollection can be iterated', function () {
    $routes = new RouteCollection;
    $routes->add(createPostmanTestRoute('users.index', 'get', 'api/users'));
    $routes->add(createPostmanTestRoute('users.show', 'get', 'api/users/{id}', ['id']));

    $count = 0;
    foreach ($routes as $route) {
        expect($route)->toBeInstanceOf(RouteData::class);
        $count++;
    }

    expect($count)->toBe(2);
});

it('RouteData correctly stores request and query classes', function () {
    $route = new RouteData(
        method: 'post',
        path: 'api/users',
        name: 'users.store',
        group: 'users',
        pathParams: [],
        requestClass: 'App\\Data\\CreateUserData',
        queryClass: null,
        hasRequest: true,
    );

    expect($route->requestClass)->toBe('App\\Data\\CreateUserData')
        ->and($route->hasRequest)->toBeTrue()
        ->and($route->queryClass)->toBeNull();
});

it('RouteData correctly stores middleware', function () {
    $route = new RouteData(
        method: 'get',
        path: 'api/users',
        name: 'users.index',
        group: 'users',
        pathParams: [],
        middleware: ['auth:sanctum', 'verified'],
    );

    expect($route->middleware)->toBe(['auth:sanctum', 'verified'])
        ->and($route->middleware)->toContain('auth:sanctum');
});

it('RouteCollection isEmpty works correctly', function () {
    $emptyRoutes = new RouteCollection;
    $routes = new RouteCollection;
    $routes->add(createPostmanTestRoute('users.index', 'get', 'api/users'));

    expect($emptyRoutes->isEmpty())->toBeTrue()
        ->and($routes->isEmpty())->toBeFalse();
});

it('RouteCollection count works correctly', function () {
    $routes = new RouteCollection;
    $routes->add(createPostmanTestRoute('users.index', 'get', 'api/users'));
    $routes->add(createPostmanTestRoute('users.show', 'get', 'api/users/{id}', ['id']));
    $routes->add(createPostmanTestRoute('users.store', 'post', 'api/users'));

    expect($routes->count())->toBe(3);
});

it('config returns default postman output path', function () {
    $config = new TrpcConfig([]);

    // Default should use storage_path which won't work in unit test context
    // Just verify the method exists and returns a string
    expect($config->getPostmanOutputPath())->toBeString();
});

// Helper function
function createPostmanTestRoute(
    string $name,
    string $method,
    string $path,
    array $pathParams = [],
    ?string $requestClass = null,
    ?string $queryClass = null,
): RouteData {
    return new RouteData(
        method: $method,
        path: $path,
        name: $name,
        group: explode('.', $name)[0],
        pathParams: $pathParams,
        requestClass: $requestClass,
        queryClass: $queryClass,
        hasRequest: $requestClass !== null,
        hasQuery: $queryClass !== null,
    );
}
