<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

/**
 * Integration tests that use the actual workbench routes.
 *
 * These tests verify the full generation pipeline works correctly
 * with real route definitions from the workbench.
 */
beforeEach(function () {
    // Clean up output directory before each test
    $outputPath = '/tmp/trpc-integration-test';
    if (File::exists($outputPath)) {
        File::deleteDirectory($outputPath);
    }
    File::makeDirectory($outputPath, 0755, true);

    // Configure the output path
    config()->set('trpc.output_path', $outputPath);
    config()->set('trpc.postman.output_path', $outputPath.'/postman');

    // Manually register workbench routes since we're in test environment
    $routesPath = dirname(__DIR__, 2).'/workbench/routes/api.php';
    if (File::exists($routesPath)) {
        Route::middleware('api')->group($routesPath);
    }

    // Merge workbench config
    $configPath = dirname(__DIR__, 2).'/workbench/config/trpc.php';
    if (File::exists($configPath)) {
        config()->set('trpc', array_merge(
            config('trpc', []),
            require $configPath
        ));
    }
});

afterEach(function () {
    // Clean up after tests
    $outputPath = '/tmp/trpc-integration-test';
    if (File::exists($outputPath)) {
        File::deleteDirectory($outputPath);
    }
});

describe('Workbench Integration', function () {
    it('generates typescript files with folder structure via artisan command', function () {
        $outputPath = '/tmp/trpc-integration-test';

        // Run the generate command
        $this->artisan('trpc:generate', [
            '--output' => $outputPath,
            '--skip-typescript-transform' => true,
            '--force' => true,
        ])->assertSuccessful();

        // Check core files
        expect(File::exists($outputPath.'/core/types.ts'))->toBeTrue();
        expect(File::exists($outputPath.'/core/fetch.ts'))->toBeTrue();
        expect(File::exists($outputPath.'/core/helpers.ts'))->toBeTrue();
        expect(File::exists($outputPath.'/core/index.ts'))->toBeTrue();

        // Check root aggregation files
        expect(File::exists($outputPath.'/routes.ts'))->toBeTrue();
        expect(File::exists($outputPath.'/api.ts'))->toBeTrue();
        expect(File::exists($outputPath.'/index.ts'))->toBeTrue();

        // Check utility files
        expect(File::exists($outputPath.'/url-builder.ts'))->toBeTrue();
        expect(File::exists($outputPath.'/client.ts'))->toBeTrue();
    });

    it('generates group folders for each resource', function () {
        $outputPath = '/tmp/trpc-integration-test';

        $this->artisan('trpc:generate', [
            '--output' => $outputPath,
            '--skip-typescript-transform' => true,
            '--force' => true,
        ])->assertSuccessful();

        // Check that group folders are created
        expect(File::exists($outputPath.'/auth/routes.ts'))->toBeTrue();
        expect(File::exists($outputPath.'/auth/api.ts'))->toBeTrue();
        expect(File::exists($outputPath.'/auth/index.ts'))->toBeTrue();

        expect(File::exists($outputPath.'/users/routes.ts'))->toBeTrue();
        expect(File::exists($outputPath.'/users/api.ts'))->toBeTrue();
        expect(File::exists($outputPath.'/users/index.ts'))->toBeTrue();

        expect(File::exists($outputPath.'/posts/routes.ts'))->toBeTrue();
        expect(File::exists($outputPath.'/posts/api.ts'))->toBeTrue();
        expect(File::exists($outputPath.'/posts/index.ts'))->toBeTrue();
    });

    it('generates routes for all workbench endpoints in group files', function () {
        $outputPath = '/tmp/trpc-integration-test';

        $this->artisan('trpc:generate', [
            '--output' => $outputPath,
            '--skip-typescript-transform' => true,
            '--force' => true,
        ])->assertSuccessful();

        // Auth routes in auth folder
        $authRoutesContent = File::get($outputPath.'/auth/routes.ts');
        expect($authRoutesContent)->toContain("'auth.login'");
        expect($authRoutesContent)->toContain("'auth.register'");
        expect($authRoutesContent)->toContain("'auth.me'");
        expect($authRoutesContent)->toContain("'auth.logout'");

        // User routes in users folder
        $usersRoutesContent = File::get($outputPath.'/users/routes.ts');
        expect($usersRoutesContent)->toContain("'users.index'");
        expect($usersRoutesContent)->toContain("'users.show'");
        expect($usersRoutesContent)->toContain("'users.store'");
        expect($usersRoutesContent)->toContain("'users.update'");
        expect($usersRoutesContent)->toContain("'users.destroy'");

        // Post routes in posts folder
        $postsRoutesContent = File::get($outputPath.'/posts/routes.ts');
        expect($postsRoutesContent)->toContain("'posts.index'");
        expect($postsRoutesContent)->toContain("'posts.show'");
        expect($postsRoutesContent)->toContain("'posts.store'");
        expect($postsRoutesContent)->toContain("'posts.destroy'");
    });

    it('generates root routes.ts with imports from group folders', function () {
        $outputPath = '/tmp/trpc-integration-test';

        $this->artisan('trpc:generate', [
            '--output' => $outputPath,
            '--skip-typescript-transform' => true,
            '--force' => true,
        ])->assertSuccessful();

        $routesContent = File::get($outputPath.'/routes.ts');

        // Should import from group folders
        expect($routesContent)->toContain("from './auth'");
        expect($routesContent)->toContain("from './users'");
        expect($routesContent)->toContain("from './posts'");

        // Should export combined RouteTypeMap
        expect($routesContent)->toContain('RouteTypeMap');
    });

    it('generates postman collection via artisan command', function () {
        $outputPath = '/tmp/trpc-integration-test';

        $this->artisan('trpc:generate', [
            '--output' => $outputPath,
            '--format' => 'postman',
            '--skip-typescript-transform' => true,
            '--force' => true,
        ])->assertSuccessful();

        // Check that postman files were generated
        expect(File::exists($outputPath.'/postman'))->toBeTrue();
        $postmanFiles = File::files($outputPath.'/postman');
        expect(count($postmanFiles))->toBeGreaterThan(0);
    });

    it('generates both typescript and postman with --format=all', function () {
        $outputPath = '/tmp/trpc-integration-test';

        $this->artisan('trpc:generate', [
            '--output' => $outputPath,
            '--format' => 'all',
            '--skip-typescript-transform' => true,
            '--force' => true,
        ])->assertSuccessful();

        // TypeScript files
        expect(File::exists($outputPath.'/routes.ts'))->toBeTrue();
        expect(File::exists($outputPath.'/core/types.ts'))->toBeTrue();

        // Postman files
        expect(File::exists($outputPath.'/postman'))->toBeTrue();
    });

    it('includes path parameters in group route definitions', function () {
        $outputPath = '/tmp/trpc-integration-test';

        $this->artisan('trpc:generate', [
            '--output' => $outputPath,
            '--skip-typescript-transform' => true,
            '--force' => true,
        ])->assertSuccessful();

        $usersRoutesContent = File::get($outputPath.'/users/routes.ts');

        // Check path parameters for user routes
        expect($usersRoutesContent)->toContain("path: 'api/users/{user}'");
    });

    it('includes HTTP methods correctly in group files', function () {
        $outputPath = '/tmp/trpc-integration-test';

        $this->artisan('trpc:generate', [
            '--output' => $outputPath,
            '--skip-typescript-transform' => true,
            '--force' => true,
        ])->assertSuccessful();

        $usersRoutesContent = File::get($outputPath.'/users/routes.ts');

        // Check various HTTP methods
        expect($usersRoutesContent)->toContain("method: 'get'");
        expect($usersRoutesContent)->toContain("method: 'post'");
        expect($usersRoutesContent)->toContain("method: 'put'");
        expect($usersRoutesContent)->toContain("method: 'delete'");
    });

    it('generates react-query files when enabled', function () {
        $outputPath = '/tmp/trpc-integration-test';

        // Enable react-query output
        config()->set('trpc.outputs.react-query', true);
        config()->set('trpc.outputs.queries', true);

        $this->artisan('trpc:generate', [
            '--output' => $outputPath,
            '--skip-typescript-transform' => true,
            '--force' => true,
        ])->assertSuccessful();

        expect(File::exists($outputPath.'/react-query.ts'))->toBeTrue();
        expect(File::exists($outputPath.'/queries.ts'))->toBeTrue();

        // Group-level queries
        expect(File::exists($outputPath.'/users/queries.ts'))->toBeTrue();

        $reactQueryContent = File::get($outputPath.'/react-query.ts');
        expect($reactQueryContent)->toContain('createQueryOptions');
        expect($reactQueryContent)->toContain('queryKey');
        expect($reactQueryContent)->toContain('gcTime');
    });

    it('generates inertia helpers when enabled', function () {
        $outputPath = '/tmp/trpc-integration-test';

        // Enable inertia output
        config()->set('trpc.outputs.inertia', true);

        $this->artisan('trpc:generate', [
            '--output' => $outputPath,
            '--skip-typescript-transform' => true,
            '--force' => true,
        ])->assertSuccessful();

        expect(File::exists($outputPath.'/inertia.ts'))->toBeTrue();

        $inertiaContent = File::get($outputPath.'/inertia.ts');
        expect($inertiaContent)->toContain("from '@inertiajs/core'");
        expect($inertiaContent)->toContain('formAction');
        expect($inertiaContent)->toContain('linkProps');
    });

    it('generates url-builder when enabled', function () {
        $outputPath = '/tmp/trpc-integration-test';

        // Enable url-builder output
        config()->set('trpc.outputs.url-builder', true);

        $this->artisan('trpc:generate', [
            '--output' => $outputPath,
            '--skip-typescript-transform' => true,
            '--force' => true,
        ])->assertSuccessful();

        expect(File::exists($outputPath.'/url-builder.ts'))->toBeTrue();

        $urlBuilderContent = File::get($outputPath.'/url-builder.ts');
        expect($urlBuilderContent)->toContain('export function url');
        expect($urlBuilderContent)->toContain('UrlOptions');
    });

    it('generates grouped-api files with factory functions', function () {
        $outputPath = '/tmp/trpc-integration-test';

        // Enable grouped-api output
        config()->set('trpc.outputs.grouped-api', true);

        $this->artisan('trpc:generate', [
            '--output' => $outputPath,
            '--skip-typescript-transform' => true,
            '--force' => true,
        ])->assertSuccessful();

        // Root api.ts
        expect(File::exists($outputPath.'/api.ts'))->toBeTrue();
        $rootApiContent = File::get($outputPath.'/api.ts');
        expect($rootApiContent)->toContain('createApi');
        expect($rootApiContent)->toContain('createAuthApi');
        expect($rootApiContent)->toContain('createUsersApi');
        expect($rootApiContent)->toContain('createPostsApi');

        // Group api.ts files
        expect(File::exists($outputPath.'/users/api.ts'))->toBeTrue();
        $usersApiContent = File::get($outputPath.'/users/api.ts');
        expect($usersApiContent)->toContain('createUsersApi');
        expect($usersApiContent)->toContain('UsersApi');
    });

    it('generates core fetch client with retry logic', function () {
        $outputPath = '/tmp/trpc-integration-test';

        $this->artisan('trpc:generate', [
            '--output' => $outputPath,
            '--skip-typescript-transform' => true,
            '--force' => true,
        ])->assertSuccessful();

        $fetchContent = File::get($outputPath.'/core/fetch.ts');
        expect($fetchContent)->toContain('calculateRetryDelay');
        expect($fetchContent)->toContain('isRetryableError');
    });

    it('generates core fetch client with CSRF support', function () {
        $outputPath = '/tmp/trpc-integration-test';

        $this->artisan('trpc:generate', [
            '--output' => $outputPath,
            '--skip-typescript-transform' => true,
            '--force' => true,
        ])->assertSuccessful();

        $fetchContent = File::get($outputPath.'/core/fetch.ts');
        expect($fetchContent)->toContain('CsrfConfig');
        expect($fetchContent)->toContain('getCsrfToken');
        expect($fetchContent)->toContain('X-XSRF-TOKEN');
    });

    it('handles 204 empty responses in core fetch', function () {
        $outputPath = '/tmp/trpc-integration-test';

        $this->artisan('trpc:generate', [
            '--output' => $outputPath,
            '--skip-typescript-transform' => true,
            '--force' => true,
        ])->assertSuccessful();

        $fetchContent = File::get($outputPath.'/core/fetch.ts');
        expect($fetchContent)->toContain('204');
        expect($fetchContent)->toContain('content-length');
    });

    // Bug fix tests
    it('does not use "default" as variable name in generated api files', function () {
        $outputPath = '/tmp/trpc-integration-test';

        config()->set('trpc.outputs.grouped-api', true);

        $this->artisan('trpc:generate', [
            '--output' => $outputPath,
            '--skip-typescript-transform' => true,
            '--force' => true,
        ])->assertSuccessful();

        $apiContent = File::get($outputPath.'/api.ts');

        // "export const default" would be a syntax error - ensure it doesn't appear
        expect($apiContent)->not->toContain('export const default');
        expect($apiContent)->not->toContain('default:');
    });

    it('auth routes have unique method names not all named index', function () {
        $outputPath = '/tmp/trpc-integration-test';

        config()->set('trpc.outputs.grouped-api', true);

        $this->artisan('trpc:generate', [
            '--output' => $outputPath,
            '--skip-typescript-transform' => true,
            '--force' => true,
        ])->assertSuccessful();

        $authApiContent = File::get($outputPath.'/auth/api.ts');

        // Auth routes should have distinct method names
        expect($authApiContent)->toContain('login:');
        expect($authApiContent)->toContain('logout:');
        expect($authApiContent)->toContain('register:');
        expect($authApiContent)->toContain('me:');
    });

    it('preset allows user to override grouped-api setting', function () {
        $outputPath = '/tmp/trpc-integration-test';

        // Set preset but override grouped-api to false
        config()->set('trpc.preset', 'inertia');
        config()->set('trpc.outputs.grouped-api', false);

        $this->artisan('trpc:generate', [
            '--output' => $outputPath,
            '--skip-typescript-transform' => true,
            '--force' => true,
        ])->assertSuccessful();

        // api.ts should NOT be generated since user overrode it
        expect(File::exists($outputPath.'/api.ts'))->toBeFalse();
        expect(File::exists($outputPath.'/users/api.ts'))->toBeFalse();
    });

    it('clears output directory when --clean flag is used', function () {
        $outputPath = '/tmp/trpc-integration-test';

        // Create a dummy file that shouldn't exist after clean
        File::makeDirectory($outputPath.'/old-folder', 0755, true);
        File::put($outputPath.'/old-folder/old-file.ts', 'old content');
        File::put($outputPath.'/old-file.ts', 'old content');

        $this->artisan('trpc:generate', [
            '--output' => $outputPath,
            '--skip-typescript-transform' => true,
            '--force' => true,
            '--clean' => true,
        ])->assertSuccessful();

        // Old files should be gone
        expect(File::exists($outputPath.'/old-folder/old-file.ts'))->toBeFalse();
        expect(File::exists($outputPath.'/old-file.ts'))->toBeFalse();

        // New files should exist
        expect(File::exists($outputPath.'/core/types.ts'))->toBeTrue();
        expect(File::exists($outputPath.'/routes.ts'))->toBeTrue();
    });

    it('creates subdirectories for group files', function () {
        $outputPath = '/tmp/trpc-integration-test';

        $this->artisan('trpc:generate', [
            '--output' => $outputPath,
            '--skip-typescript-transform' => true,
            '--force' => true,
        ])->assertSuccessful();

        // Subdirectories should be created
        expect(File::isDirectory($outputPath.'/core'))->toBeTrue();
        expect(File::isDirectory($outputPath.'/auth'))->toBeTrue();
        expect(File::isDirectory($outputPath.'/users'))->toBeTrue();
        expect(File::isDirectory($outputPath.'/posts'))->toBeTrue();
    });
})->group('integration');
