<?php

declare(strict_types=1);

use OybekDaniyarov\LaravelTrpc\Data\Context\GeneratorContext;
use OybekDaniyarov\LaravelTrpc\TrpcConfig;

// Note: Tests for forTypeScript() and forPostman() static methods are in Feature tests
// because they call TrpcConfig methods that evaluate resource_path()/storage_path()
// even when config values are provided.

it('creates with constructor', function () {
    $config = new TrpcConfig([
        'output_path' => '/path/to/output',
    ]);

    $context = new GeneratorContext(
        outputPath: '/path/to/output',
        config: $config,
    );

    expect($context->outputPath)->toBe('/path/to/output')
        ->and($context->config)->toBe($config)
        ->and($context->postman)->toBeFalse()
        ->and($context->postmanEnv)->toBeFalse()
        ->and($context->skipTypeScriptTransform)->toBeFalse();
});

it('creates with all parameters', function () {
    $config = new TrpcConfig([
        'api_prefix' => 'api',
    ]);

    $context = new GeneratorContext(
        outputPath: '/output',
        config: $config,
        postman: true,
        postmanEnv: true,
        skipTypeScriptTransform: true,
    );

    expect($context->postman)->toBeTrue()
        ->and($context->postmanEnv)->toBeTrue()
        ->and($context->skipTypeScriptTransform)->toBeTrue();
});

it('is readonly', function () {
    $config = new TrpcConfig([
        'api_prefix' => 'api',
    ]);

    $context = new GeneratorContext(
        outputPath: '/output',
        config: $config,
    );

    // These should be accessible as readonly properties
    expect($context->outputPath)->toBe('/output')
        ->and($context->config)->toBe($config)
        ->and($context->postman)->toBeFalse()
        ->and($context->postmanEnv)->toBeFalse()
        ->and($context->skipTypeScriptTransform)->toBeFalse();
});

it('postman defaults to false', function () {
    $config = new TrpcConfig([
        'api_prefix' => 'api',
    ]);

    $context = new GeneratorContext(
        outputPath: '/output',
        config: $config,
    );

    expect($context->postman)->toBeFalse();
});

it('postmanEnv defaults to false', function () {
    $config = new TrpcConfig([
        'api_prefix' => 'api',
    ]);

    $context = new GeneratorContext(
        outputPath: '/output',
        config: $config,
    );

    expect($context->postmanEnv)->toBeFalse();
});

it('skipTypeScriptTransform defaults to false', function () {
    $config = new TrpcConfig([
        'api_prefix' => 'api',
    ]);

    $context = new GeneratorContext(
        outputPath: '/output',
        config: $config,
    );

    expect($context->skipTypeScriptTransform)->toBeFalse();
});
