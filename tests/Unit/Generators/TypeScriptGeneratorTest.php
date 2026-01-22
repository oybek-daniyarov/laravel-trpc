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

    // Core files
    expect($result->files)->toHaveKey('core/types.ts')
        ->and($result->files)->toHaveKey('core/fetch.ts')
        ->and($result->files)->toHaveKey('core/helpers.ts')
        ->and($result->files)->toHaveKey('core/index.ts');

    // Group files (users group)
    expect($result->files)->toHaveKey('users/routes.ts')
        ->and($result->files)->toHaveKey('users/api.ts')
        ->and($result->files)->toHaveKey('users/index.ts');

    // Root aggregation files
    expect($result->files)->toHaveKey('routes.ts')
        ->and($result->files)->toHaveKey('api.ts')
        ->and($result->files)->toHaveKey('index.ts')
        ->and($result->files)->toHaveKey('README.md');

    // Utility files
    expect($result->files)->toHaveKey('url-builder.ts')
        ->and($result->files)->toHaveKey('client.ts')
        ->and($result->files)->toHaveKey('inertia.ts');
});

it('includes route definitions in group routes.ts', function () {
    $routes = createTestRouteCollection();

    $result = $this->generator->generate($routes, $this->context);
    $routesContent = $result->files['users/routes.ts'];

    expect($routesContent)->toContain("'users.index'")
        ->and($routesContent)->toContain("'users.show'")
        ->and($routesContent)->toContain("'users.store'")
        ->and($routesContent)->toContain('api/users')
        ->and($routesContent)->toContain('method:');
});

it('includes route definitions in root routes.ts', function () {
    $routes = createTestRouteCollection();

    $result = $this->generator->generate($routes, $this->context);
    $routesContent = $result->files['routes.ts'];

    expect($routesContent)->toContain('usersRoutes')
        ->and($routesContent)->toContain('RouteTypeMap');
});

it('handles empty route collection', function () {
    $routes = new RouteCollection;

    $result = $this->generator->generate($routes, $this->context);

    // Core files are always generated
    expect($result->files)->toHaveKey('core/types.ts')
        ->and($result->files)->toHaveKey('core/fetch.ts')
        ->and($result->files)->toHaveKey('core/index.ts');

    // Root files are generated but may be empty
    expect($result->files)->toHaveKey('routes.ts')
        ->and($result->files)->toHaveKey('api.ts');
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

    expect($result->files)->toHaveKey('users/routes.ts')
        ->and($result->files)->toHaveKey('core/types.ts')
        ->and($result->files)->not->toHaveKey('core/helpers.ts')
        ->and($result->files)->not->toHaveKey('core/fetch.ts')
        ->and($result->files)->not->toHaveKey('users/api.ts');
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
        ->and($result->files)->toHaveKey('queries.ts')
        ->and($result->files)->toHaveKey('users/queries.ts');
});

it('groups routes by group property into separate folders', function () {
    $routes = new RouteCollection;
    $routes->add(createTestRoute('users.index', 'get', 'api/users', 'users'));
    $routes->add(createTestRoute('users.show', 'get', 'api/users/{id}', 'users', ['id']));
    $routes->add(createTestRoute('posts.index', 'get', 'api/posts', 'posts'));

    $result = $this->generator->generate($routes, $this->context);

    // Should have separate folders for each group
    expect($result->files)->toHaveKey('users/routes.ts')
        ->and($result->files)->toHaveKey('users/api.ts')
        ->and($result->files)->toHaveKey('posts/routes.ts')
        ->and($result->files)->toHaveKey('posts/api.ts');

    // Root api.ts should reference both groups
    $apiContent = $result->files['api.ts'];
    expect($apiContent)->toContain('createUsersApi')
        ->and($apiContent)->toContain('createPostsApi');
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
    $routesContent = $result->files['users/routes.ts'];

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
    $routesContent = $result->files['users/routes.ts'];

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
    $routesContent = $result->files['users/routes.ts'];

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
    $routesContent = $result->files['users/routes.ts'];

    // Collection routes should have their response type in the generated file
    expect($routesContent)->toContain('App.Data.UserData')
        ->and($routesContent)->toContain("'users.all'");
});

it('deduplicates routes with same name', function () {
    $routes = new RouteCollection;
    $routes->add(createTestRoute('users.index', 'get', 'api/users', 'users'));
    $routes->add(createTestRoute('users.index', 'get', 'api/v2/users', 'users'));

    $result = $this->generator->generate($routes, $this->context);
    $routesContent = $result->files['users/routes.ts'];

    expect($routesContent)->toContain("'users.index'")
        ->and($routesContent)->toContain("'users.index_1'");
});

it('generates core files with proper structure', function () {
    $routes = createTestRouteCollection();

    $result = $this->generator->generate($routes, $this->context);

    // Check core types
    $typesContent = $result->files['core/types.ts'];
    expect($typesContent)->toContain('ApiError')
        ->and($typesContent)->toContain('PaginatedResponse')
        ->and($typesContent)->toContain('ValidationError');

    // Check core fetch
    $fetchContent = $result->files['core/fetch.ts'];
    expect($fetchContent)->toContain('fetchApi')
        ->and($fetchContent)->toContain('ApiClientConfig')
        ->and($fetchContent)->toContain('RequestOptions');

    // Check core index exports
    $coreIndexContent = $result->files['core/index.ts'];
    expect($coreIndexContent)->toContain("from './types'")
        ->and($coreIndexContent)->toContain("from './fetch'");
});

it('generates group files with factory functions', function () {
    $routes = createTestRouteCollection();

    $result = $this->generator->generate($routes, $this->context);

    // Check group api exports factory function
    $apiContent = $result->files['users/api.ts'];
    expect($apiContent)->toContain('createUsersApi')
        ->and($apiContent)->toContain('UsersApi');

    // Check group index exports
    $groupIndexContent = $result->files['users/index.ts'];
    expect($groupIndexContent)->toContain('createUsersApi')
        ->and($groupIndexContent)->toContain('usersRoutes');
});

it('generates group queries without infiniteQueryOptions when no paginated routes', function () {
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

    // Create routes without any paginated GET routes
    $routes = new RouteCollection;
    $routes->add(createTestRoute('users.index', 'get', 'api/users', 'users'));
    $routes->add(createTestRoute('users.show', 'get', 'api/users/{id}', 'users', ['id']));
    $routes->add(createTestRoute('users.store', 'post', 'api/users', 'users'));

    $result = $generator->generate($routes, $context);
    $queriesContent = $result->files['users/queries.ts'];

    // Should import queryOptions but NOT infiniteQueryOptions
    expect($queriesContent)->toContain("import { queryOptions } from '@tanstack/react-query'")
        ->and($queriesContent)->not->toContain('infiniteQueryOptions');
});

it('generates group queries with infiniteQueryOptions when paginated routes exist', function () {
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

    // Create routes with a paginated GET route
    $routes = new RouteCollection;
    $routes->add(new RouteData(
        method: 'get',
        path: 'api/users',
        name: 'users.index',
        group: 'users',
        pathParams: [],
        responseType: 'App.Data.UserData',
        hasResponse: true,
        isPaginated: true,
    ));
    $routes->add(createTestRoute('users.show', 'get', 'api/users/{id}', 'users', ['id']));

    $result = $generator->generate($routes, $context);
    $queriesContent = $result->files['users/queries.ts'];

    // Should import both queryOptions and infiniteQueryOptions
    expect($queriesContent)->toContain("import { queryOptions, infiniteQueryOptions } from '@tanstack/react-query'");
});

it('generates mutations files when enabled', function () {
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
            'mutations' => true,
        ],
    ]);

    $generator = new TypeScriptGenerator($config, $this->stubRenderer);
    $context = new GeneratorContext('/tmp/trpc-test/api', $config);
    $routes = createTestRouteCollection();

    $result = $generator->generate($routes, $context);

    expect($result->files)->toHaveKey('mutations.ts')
        ->and($result->files)->toHaveKey('users/mutations.ts');
});

it('generates mutations with correct structure', function () {
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
            'mutations' => true,
        ],
    ]);

    $generator = new TypeScriptGenerator($config, $this->stubRenderer);
    $context = new GeneratorContext('/tmp/trpc-test/api', $config);
    $routes = createTestRouteCollection();

    $result = $generator->generate($routes, $context);
    $mutationsContent = $result->files['users/mutations.ts'];

    // Should have mutation keys
    expect($mutationsContent)->toContain('usersMutationKeys')
        // Should have factory function
        ->and($mutationsContent)->toContain('createUsersMutations')
        // Should import UseMutationOptions from @tanstack/react-query
        ->and($mutationsContent)->toContain("import type { UseMutationOptions } from '@tanstack/react-query'")
        // Should have store mutation (POST route)
        ->and($mutationsContent)->toContain('store:');
});

it('only generates mutations for groups with mutation routes', function () {
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
            'mutations' => true,
        ],
    ]);

    $generator = new TypeScriptGenerator($config, $this->stubRenderer);
    $context = new GeneratorContext('/tmp/trpc-test/api', $config);

    // Create routes - only GET routes for one group
    $routes = new RouteCollection;
    $routes->add(createTestRoute('readonly.index', 'get', 'api/readonly', 'readonly'));
    $routes->add(createTestRoute('readonly.show', 'get', 'api/readonly/{id}', 'readonly', ['id']));
    $routes->add(createTestRoute('writable.store', 'post', 'api/writable', 'writable'));

    $result = $generator->generate($routes, $context);

    // Should NOT have mutations file for readonly group
    expect($result->files)->not->toHaveKey('readonly/mutations.ts')
        // Should have mutations file for writable group
        ->and($result->files)->toHaveKey('writable/mutations.ts');
});

it('exports mutations from group index when mutations are enabled', function () {
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
            'mutations' => true,
        ],
    ]);

    $generator = new TypeScriptGenerator($config, $this->stubRenderer);
    $context = new GeneratorContext('/tmp/trpc-test/api', $config);
    $routes = createTestRouteCollection();

    $result = $generator->generate($routes, $context);
    $indexContent = $result->files['users/index.ts'];

    expect($indexContent)->toContain('usersMutationKeys')
        ->and($indexContent)->toContain('createUsersMutations')
        ->and($indexContent)->toContain("from './mutations'");
});

it('exports createMutations from root index when mutations are enabled', function () {
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
            'mutations' => true,
        ],
    ]);

    $generator = new TypeScriptGenerator($config, $this->stubRenderer);
    $context = new GeneratorContext('/tmp/trpc-test/api', $config);
    $routes = createTestRouteCollection();

    $result = $generator->generate($routes, $context);
    $indexContent = $result->files['index.ts'];

    expect($indexContent)->toContain('createMutations')
        ->and($indexContent)->toContain("from './mutations'");
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
