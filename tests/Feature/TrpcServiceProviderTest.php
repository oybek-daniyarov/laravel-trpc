<?php

declare(strict_types=1);

use OybekDaniyarov\LaravelTrpc\TrpcConfig;

// Note: Full service provider tests require running from package directory with its own testbench setup.
// These tests verify the basic functionality that works without full Laravel app context.

it('TrpcConfig can be created with defaults', function () {
    $config = new TrpcConfig([]);

    expect($config->getApiPrefix())->toBe('api')
        ->and($config->getExcludeMethods())->toBe(['options', 'head']);
});

it('TrpcConfig can be created with custom values', function () {
    $config = new TrpcConfig([
        'api_prefix' => 'api/v2',
        'exclude_methods' => ['options'],
    ]);

    expect($config->getApiPrefix())->toBe('api/v2')
        ->and($config->getExcludeMethods())->toBe(['options']);
});

it('TrpcConfig returns default version', function () {
    $config = new TrpcConfig([]);

    expect($config->getVersion())->toBe('v1');
});

it('TrpcConfig returns custom version', function () {
    $config = new TrpcConfig([
        'version' => 'v3',
    ]);

    expect($config->getVersion())->toBe('v3');
});

it('TrpcConfig returns route groups', function () {
    $config = new TrpcConfig([
        'route_groups' => [
            'auth' => ['login', 'logout'],
            'users' => ['users'],
        ],
    ]);

    $groups = $config->getRouteGroups();

    expect($groups)->toHaveKey('auth')
        ->and($groups)->toHaveKey('users')
        ->and($groups['auth'])->toContain('login');
});

it('TrpcConfig returns transformers', function () {
    $config = new TrpcConfig([
        'transformers' => [
            'CustomType' => 'CustomTransformer',
        ],
    ]);

    $transformers = $config->getTransformers();

    expect($transformers)->toHaveKey('CustomType')
        ->and($transformers['CustomType'])->toBe('CustomTransformer');
});

it('TrpcConfig returns postman settings', function () {
    $config = new TrpcConfig([
        'postman' => [
            'base_url' => 'https://api.example.com',
            'auth_type' => 'apikey',
            'default_headers' => [
                'X-API-Key' => '{{api_key}}',
            ],
        ],
    ]);

    expect($config->getPostmanBaseUrl())->toBe('https://api.example.com')
        ->and($config->getPostmanAuthType())->toBe('apikey')
        ->and($config->getPostmanDefaultHeaders())->toHaveKey('X-API-Key');
});
