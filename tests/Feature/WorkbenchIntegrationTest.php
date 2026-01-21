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
    it('generates typescript files via artisan command', function () {
        $outputPath = '/tmp/trpc-integration-test';

        // Run the generate command
        $this->artisan('trpc:generate', [
            '--output' => $outputPath,
            '--skip-typescript-transform' => true,
            '--force' => true,
        ])->assertSuccessful();

        // Check that files were generated
        expect(File::exists($outputPath.'/routes.ts'))->toBeTrue();
        expect(File::exists($outputPath.'/types.ts'))->toBeTrue();
        expect(File::exists($outputPath.'/helpers.ts'))->toBeTrue();
        expect(File::exists($outputPath.'/fetch.ts'))->toBeTrue();
        expect(File::exists($outputPath.'/client.ts'))->toBeTrue();
        expect(File::exists($outputPath.'/index.ts'))->toBeTrue();
    });

    it('generates routes for all workbench endpoints', function () {
        $outputPath = '/tmp/trpc-integration-test';

        $this->artisan('trpc:generate', [
            '--output' => $outputPath,
            '--skip-typescript-transform' => true,
            '--force' => true,
        ])->assertSuccessful();

        $routesContent = File::get($outputPath.'/routes.ts');

        // Auth routes
        expect($routesContent)->toContain("'auth.login'");
        expect($routesContent)->toContain("'auth.register'");
        expect($routesContent)->toContain("'auth.me'");
        expect($routesContent)->toContain("'auth.logout'");

        // User routes
        expect($routesContent)->toContain("'users.index'");
        expect($routesContent)->toContain("'users.show'");
        expect($routesContent)->toContain("'users.store'");
        expect($routesContent)->toContain("'users.update'");
        expect($routesContent)->toContain("'users.destroy'");

        // Post routes
        expect($routesContent)->toContain("'posts.index'");
        expect($routesContent)->toContain("'posts.show'");
        expect($routesContent)->toContain("'posts.store'");
        expect($routesContent)->toContain("'posts.destroy'");

        // Nested comment routes
        expect($routesContent)->toContain("'posts.comments.index'");
        expect($routesContent)->toContain("'posts.comments.show'");
        expect($routesContent)->toContain("'posts.comments.store'");
        expect($routesContent)->toContain("'posts.comments.destroy'");
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
        expect(File::exists($outputPath.'/types.ts'))->toBeTrue();

        // Postman files
        expect(File::exists($outputPath.'/postman'))->toBeTrue();
    });

    it('includes path parameters in route definitions', function () {
        $outputPath = '/tmp/trpc-integration-test';

        $this->artisan('trpc:generate', [
            '--output' => $outputPath,
            '--skip-typescript-transform' => true,
            '--force' => true,
        ])->assertSuccessful();

        $routesContent = File::get($outputPath.'/routes.ts');

        // Check path parameters for user routes
        expect($routesContent)->toContain("path: 'api/users/{user}'");

        // Check path parameters for nested routes (post and comment)
        expect($routesContent)->toContain("path: 'api/posts/{post}/comments/{comment}'");
    });

    it('includes HTTP methods correctly', function () {
        $outputPath = '/tmp/trpc-integration-test';

        $this->artisan('trpc:generate', [
            '--output' => $outputPath,
            '--skip-typescript-transform' => true,
            '--force' => true,
        ])->assertSuccessful();

        $routesContent = File::get($outputPath.'/routes.ts');

        // Check various HTTP methods
        expect($routesContent)->toContain("method: 'get'");
        expect($routesContent)->toContain("method: 'post'");
        expect($routesContent)->toContain("method: 'put'");
        expect($routesContent)->toContain("method: 'delete'");
    });

    it('generates react-query hooks when enabled', function () {
        $outputPath = '/tmp/trpc-integration-test';

        // Enable react-query output
        config()->set('trpc.outputs.react-query', true);

        $this->artisan('trpc:generate', [
            '--output' => $outputPath,
            '--skip-typescript-transform' => true,
            '--force' => true,
        ])->assertSuccessful();

        expect(File::exists($outputPath.'/react-query.ts'))->toBeTrue();

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

    it('generates grouped-api file when enabled', function () {
        $outputPath = '/tmp/trpc-integration-test';

        // Enable grouped-api output
        config()->set('trpc.outputs.grouped-api', true);

        $this->artisan('trpc:generate', [
            '--output' => $outputPath,
            '--skip-typescript-transform' => true,
            '--force' => true,
        ])->assertSuccessful();

        expect(File::exists($outputPath.'/api.ts'))->toBeTrue();

        $apiContent = File::get($outputPath.'/api.ts');
        // Check for grouped structure
        expect($apiContent)->toContain('auth');
        expect($apiContent)->toContain('users');
        expect($apiContent)->toContain('posts');
    });

    it('generates fetch client with retry logic', function () {
        $outputPath = '/tmp/trpc-integration-test';

        $this->artisan('trpc:generate', [
            '--output' => $outputPath,
            '--skip-typescript-transform' => true,
            '--force' => true,
        ])->assertSuccessful();

        $fetchContent = File::get($outputPath.'/fetch.ts');
        expect($fetchContent)->toContain('calculateRetryDelay');
        expect($fetchContent)->toContain('isRetryableError');
        expect($fetchContent)->toContain('maxRetries');
    });

    it('generates fetch client with CSRF support', function () {
        $outputPath = '/tmp/trpc-integration-test';

        $this->artisan('trpc:generate', [
            '--output' => $outputPath,
            '--skip-typescript-transform' => true,
            '--force' => true,
        ])->assertSuccessful();

        $fetchContent = File::get($outputPath.'/fetch.ts');
        expect($fetchContent)->toContain('CsrfConfig');
        expect($fetchContent)->toContain('getCsrfToken');
        expect($fetchContent)->toContain('X-XSRF-TOKEN');
    });

    it('handles 204 empty responses in fetch', function () {
        $outputPath = '/tmp/trpc-integration-test';

        $this->artisan('trpc:generate', [
            '--output' => $outputPath,
            '--skip-typescript-transform' => true,
            '--force' => true,
        ])->assertSuccessful();

        $fetchContent = File::get($outputPath.'/fetch.ts');
        expect($fetchContent)->toContain('204');
        expect($fetchContent)->toContain('content-length');
    });

    // Bug fix tests
    it('does not use "default" as variable name in generated api.ts', function () {
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
        expect($apiContent)->not->toContain('default,');
    });

    it('uses misc as fallback group name instead of default', function () {
        $outputPath = '/tmp/trpc-integration-test';

        // Register a route with no name that would get empty group
        Route::get('/api/misc-test', fn () => 'test');

        config()->set('trpc.outputs.grouped-api', true);

        $this->artisan('trpc:generate', [
            '--output' => $outputPath,
            '--skip-typescript-transform' => true,
            '--force' => true,
        ])->assertSuccessful();

        $apiContent = File::get($outputPath.'/api.ts');

        // Should use "misc" instead of "default" for fallback group
        expect($apiContent)->not->toContain('export const default');
    });

    it('auth routes have unique method names not all named index', function () {
        $outputPath = '/tmp/trpc-integration-test';

        config()->set('trpc.outputs.grouped-api', true);

        $this->artisan('trpc:generate', [
            '--output' => $outputPath,
            '--skip-typescript-transform' => true,
            '--force' => true,
        ])->assertSuccessful();

        $apiContent = File::get($outputPath.'/api.ts');

        // Auth routes should have distinct method names
        expect($apiContent)->toContain('login:');
        expect($apiContent)->toContain('logout:');
        expect($apiContent)->toContain('register:');
        expect($apiContent)->toContain('me:');

        // Count occurrences of 'index:' in auth section - should be 0 or 1, not multiple
        preg_match('/export const auth = \{(.*?)\};/s', $apiContent, $matches);
        if (isset($matches[1])) {
            $authSection = $matches[1];
            $indexCount = substr_count($authSection, 'index:');
            expect($indexCount)->toBeLessThanOrEqual(1);
        }
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
    });
})->group('integration');
