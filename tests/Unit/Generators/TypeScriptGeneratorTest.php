<?php

declare(strict_types=1);

use OybekDaniyarov\LaravelTrpc\Collections\RouteCollection;
use OybekDaniyarov\LaravelTrpc\Data\Context\GeneratorContext;
use OybekDaniyarov\LaravelTrpc\Data\RouteData;
use OybekDaniyarov\LaravelTrpc\Generators\TypeScriptGenerator;
use OybekDaniyarov\LaravelTrpc\Services\StubRenderer;
use OybekDaniyarov\LaravelTrpc\TrpcConfig;

beforeEach(function () {
    $this->config = new TrpcConfig([
        'output_path' => '/tmp/trpc-test/api',
        'outputs' => [
            'routes' => true,
            'types' => true,
            'helpers' => true,
            'url-builder' => true,
            'fetch' => true,
            'client' => true,
            'index' => true,
            'readme' => true,
            'grouped-api' => true,
            'inertia' => true,
            'react-query' => false,
            'queries' => false,
        ],
    ]);

    $this->stubRenderer = app(StubRenderer::class);

    $this->generator = new TypeScriptGenerator(
        $this->config,
        $this->stubRenderer,
    );

    $this->context = new GeneratorContext(
        outputPath: '/tmp/trpc-test/api',
        config: $this->config,
    );
});

it('generates all expected files for default outputs', function () {
    $routes = createTestRouteCollection();

    $result = $this->generator->generate($routes, $this->context);

    expect($result->files)->toHaveKey('routes.ts')
        ->and($result->files)->toHaveKey('types.ts')
        ->and($result->files)->toHaveKey('helpers.ts')
        ->and($result->files)->toHaveKey('url-builder.ts')
        ->and($result->files)->toHaveKey('fetch.ts')
        ->and($result->files)->toHaveKey('client.ts')
        ->and($result->files)->toHaveKey('index.ts')
        ->and($result->files)->toHaveKey('README.md')
        ->and($result->files)->toHaveKey('api.ts')
        ->and($result->files)->toHaveKey('inertia.ts');
});

it('includes route definitions in routes.ts', function () {
    $routes = createTestRouteCollection();

    $result = $this->generator->generate($routes, $this->context);
    $routesContent = $result->files['routes.ts'];

    expect($routesContent)->toContain("'users.index'")
        ->and($routesContent)->toContain("'users.show'")
        ->and($routesContent)->toContain("'users.store'")
        ->and($routesContent)->toContain('api/users')
        ->and($routesContent)->toContain('method:');
});

it('handles empty route collection', function () {
    $routes = new RouteCollection;

    $result = $this->generator->generate($routes, $this->context);

    expect($result->files)->toHaveKey('routes.ts')
        ->and($result->files['routes.ts'])->toContain('routes');
});

it('respects disabled outputs', function () {
    $config = new TrpcConfig([
        'outputs' => [
            'routes' => true,
            'types' => true,
            'helpers' => false,
            'url-builder' => false,
            'fetch' => false,
            'client' => false,
            'index' => false,
            'readme' => false,
            'grouped-api' => false,
            'inertia' => false,
            'react-query' => false,
            'queries' => false,
        ],
    ]);

    $generator = new TypeScriptGenerator($config, $this->stubRenderer);
    $context = new GeneratorContext('/tmp/trpc-test/api', $config);
    $routes = createTestRouteCollection();

    $result = $generator->generate($routes, $context);

    expect($result->files)->toHaveKey('routes.ts')
        ->and($result->files)->toHaveKey('types.ts')
        ->and($result->files)->not->toHaveKey('helpers.ts')
        ->and($result->files)->not->toHaveKey('fetch.ts')
        ->and($result->files)->not->toHaveKey('api.ts');
});

it('generates react-query files when enabled', function () {
    $config = new TrpcConfig([
        'outputs' => [
            'routes' => true,
            'types' => true,
            'helpers' => true,
            'url-builder' => true,
            'fetch' => true,
            'client' => true,
            'index' => true,
            'readme' => false,
            'grouped-api' => true,
            'inertia' => false,
            'react-query' => true,
            'queries' => true,
        ],
    ]);

    $generator = new TypeScriptGenerator($config, $this->stubRenderer);
    $context = new GeneratorContext('/tmp/trpc-test/api', $config);
    $routes = createTestRouteCollection();

    $result = $generator->generate($routes, $context);

    expect($result->files)->toHaveKey('react-query.ts')
        ->and($result->files)->toHaveKey('queries.ts');
});

it('groups routes by group property', function () {
    $routes = new RouteCollection;
    $routes->add(createTestRoute('users.index', 'get', 'api/users', 'users'));
    $routes->add(createTestRoute('users.show', 'get', 'api/users/{id}', 'users', ['id']));
    $routes->add(createTestRoute('posts.index', 'get', 'api/posts', 'posts'));

    $result = $this->generator->generate($routes, $this->context);
    $apiContent = $result->files['api.ts'];

    expect($apiContent)->toContain('users')
        ->and($apiContent)->toContain('posts');
});

it('handles routes with request types', function () {
    $route = new RouteData(
        method: 'post',
        path: 'api/users',
        name: 'users.store',
        group: 'users',
        pathParams: [],
        requestType: 'App.Data.CreateUserData',
        responseType: 'App.Data.UserData',
        hasRequest: true,
        hasResponse: true,
    );

    $routes = new RouteCollection;
    $routes->add($route);

    $result = $this->generator->generate($routes, $this->context);
    $routesContent = $result->files['routes.ts'];

    expect($routesContent)->toContain('App.Data.CreateUserData')
        ->and($routesContent)->toContain('App.Data.UserData');
});

it('handles routes with query types', function () {
    $route = new RouteData(
        method: 'get',
        path: 'api/users',
        name: 'users.index',
        group: 'users',
        pathParams: [],
        queryType: 'App.Data.UserFilterData',
        responseType: 'App.Data.UserData',
        hasQuery: true,
        hasResponse: true,
        isPaginated: true,
    );

    $routes = new RouteCollection;
    $routes->add($route);

    $result = $this->generator->generate($routes, $this->context);
    $routesContent = $result->files['routes.ts'];

    expect($routesContent)->toContain('App.Data.UserFilterData');
});

it('handles paginated routes by including response type', function () {
    $route = new RouteData(
        method: 'get',
        path: 'api/users',
        name: 'users.index',
        group: 'users',
        pathParams: [],
        responseType: 'App.Data.UserData',
        hasResponse: true,
        isPaginated: true,
    );

    $routes = new RouteCollection;
    $routes->add($route);

    $result = $this->generator->generate($routes, $this->context);
    $routesContent = $result->files['routes.ts'];

    // Paginated routes should have their response type in the generated file
    expect($routesContent)->toContain('App.Data.UserData')
        ->and($routesContent)->toContain("'users.index'");
});

it('handles collection routes by including response type', function () {
    $route = new RouteData(
        method: 'get',
        path: 'api/users',
        name: 'users.all',
        group: 'users',
        pathParams: [],
        responseType: 'App.Data.UserData',
        hasResponse: true,
        isCollection: true,
    );

    $routes = new RouteCollection;
    $routes->add($route);

    $result = $this->generator->generate($routes, $this->context);
    $routesContent = $result->files['routes.ts'];

    // Collection routes should have their response type in the generated file
    expect($routesContent)->toContain('App.Data.UserData')
        ->and($routesContent)->toContain("'users.all'");
});

it('deduplicates routes with same name', function () {
    $routes = new RouteCollection;
    $routes->add(createTestRoute('users.index', 'get', 'api/users', 'users'));
    $routes->add(createTestRoute('users.index', 'get', 'api/v2/users', 'users'));

    $result = $this->generator->generate($routes, $this->context);
    $routesContent = $result->files['routes.ts'];

    expect($routesContent)->toContain("'users.index'")
        ->and($routesContent)->toContain("'users.index_1'");
});

// Helper functions
function createTestRouteCollection(): RouteCollection
{
    $routes = new RouteCollection;
    $routes->add(createTestRoute('users.index', 'get', 'api/users', 'users'));
    $routes->add(createTestRoute('users.show', 'get', 'api/users/{id}', 'users', ['id']));
    $routes->add(createTestRoute('users.store', 'post', 'api/users', 'users'));

    return $routes;
}

function createTestRoute(
    string $name,
    string $method,
    string $path,
    string $group,
    array $pathParams = [],
    ?string $requestType = null,
    ?string $responseType = null,
): RouteData {
    return new RouteData(
        method: $method,
        path: $path,
        name: $name,
        group: $group,
        pathParams: $pathParams,
        requestType: $requestType,
        responseType: $responseType,
        hasRequest: $requestType !== null,
        hasResponse: $responseType !== null,
    );
}
