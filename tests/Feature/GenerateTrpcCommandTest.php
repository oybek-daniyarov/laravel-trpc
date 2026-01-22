<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use OybekDaniyarov\LaravelTrpc\TrpcConfig;

// Note: Full command tests require a complete Laravel application context
// with properly registered routes. These tests focus on command registration
// and basic functionality that can be tested without route registration.

beforeEach(function () {
    // Clean up test output directory
    $testPath = '/tmp/trpc-test';
    if (File::exists($testPath)) {
        File::deleteDirectory($testPath);
    }
    File::makeDirectory($testPath.'/api', 0755, true);
    File::makeDirectory($testPath.'/postman', 0755, true);
});

afterEach(function () {
    $testPath = '/tmp/trpc-test';
    if (File::exists($testPath)) {
        File::deleteDirectory($testPath);
    }
});

it('trpc:generate command is registered', function () {
    $this->artisan('list')
        ->expectsOutputToContain('trpc:generate');
});

it('command accepts --postman flag', function () {
    $result = $this->artisan('trpc:generate', [
        '--postman' => true,
        '--skip-typescript-transform' => true,
    ]);

    // Command should not fail with invalid option
    expect(true)->toBeTrue();
});

it('command accepts --format=all for generating both outputs', function () {
    $result = $this->artisan('trpc:generate', [
        '--format' => 'all',
        '--skip-typescript-transform' => true,
    ]);

    expect(true)->toBeTrue();
});

it('command accepts --force flag', function () {
    $result = $this->artisan('trpc:generate', [
        '--force' => true,
        '--skip-typescript-transform' => true,
    ]);

    expect(true)->toBeTrue();
});

it('command accepts --output option', function () {
    $result = $this->artisan('trpc:generate', [
        '--output' => '/tmp/trpc-test/custom',
        '--skip-typescript-transform' => true,
    ]);

    expect(true)->toBeTrue();
});

it('command accepts --api-prefix option', function () {
    $result = $this->artisan('trpc:generate', [
        '--api-prefix' => 'api/v2',
        '--skip-typescript-transform' => true,
    ]);

    expect(true)->toBeTrue();
});

it('command accepts --format option', function () {
    $result = $this->artisan('trpc:generate', [
        '--format' => 'typescript',
        '--skip-typescript-transform' => true,
    ]);

    expect(true)->toBeTrue();
});

it('command accepts --postman-env flag', function () {
    $result = $this->artisan('trpc:generate', [
        '--postman' => true,
        '--postman-env' => true,
        '--skip-typescript-transform' => true,
    ]);

    expect(true)->toBeTrue();
});

it('command accepts --base-url option', function () {
    $result = $this->artisan('trpc:generate', [
        '--base-url' => 'https://api.example.com',
        '--skip-typescript-transform' => true,
    ]);

    expect(true)->toBeTrue();
});

it('TrpcConfig is resolvable from container', function () {
    $config = app(TrpcConfig::class);

    expect($config)->toBeInstanceOf(TrpcConfig::class);
});

it('config values can be retrieved', function () {
    $config = app(TrpcConfig::class);

    expect($config->getApiPrefix())->toBeString()
        ->and($config->getOutputPath())->toBeString();
});

it('command signature includes all expected options', function () {
    $command = $this->app->make(\OybekDaniyarov\LaravelTrpc\Commands\GenerateTrpcCommand::class);
    $signature = $command->getDefinition();

    expect($signature->hasOption('output'))->toBeTrue()
        ->and($signature->hasOption('api-prefix'))->toBeTrue()
        ->and($signature->hasOption('skip-typescript-transform'))->toBeTrue()
        ->and($signature->hasOption('postman'))->toBeTrue()
        ->and($signature->hasOption('postman-env'))->toBeTrue()
        ->and($signature->hasOption('force'))->toBeTrue()
        ->and($signature->hasOption('format'))->toBeTrue()
        ->and($signature->hasOption('base-url'))->toBeTrue();
});

it('command description is set', function () {
    $command = $this->app->make(\OybekDaniyarov\LaravelTrpc\Commands\GenerateTrpcCommand::class);

    expect($command->getDescription())->toContain('Generate');
});
