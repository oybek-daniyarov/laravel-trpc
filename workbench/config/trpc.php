<?php

declare(strict_types=1);

return [
    // Output to workbench resources/js/api
    'output_path' => __DIR__.'/../resources/js/api',

    'api_prefix' => 'api',
    'version' => 'v1',
    'route_mode' => 'api',

    'include_patterns' => [],
    'exclude_patterns' => [
        'debugbar.*',
        'horizon.*',
        'telescope.*',
        'sanctum.*',
        'ignition.*',
    ],

    'exclude_methods' => ['options', 'head'],

    'route_name_mappings' => [],

    'route_groups' => [
        'auth' => ['auth'],
        'users' => ['users'],
        'posts' => ['posts', 'posts.comments'],
    ],

    'preset' => null,

    'outputs' => [
        // Core files
        'routes' => true,
        'types' => true,
        'helpers' => true,
        'url-builder' => true,
        'fetch' => true,
        'client' => true,
        'index' => true,
        'readme' => true,

        // Grouped API client
        'grouped-api' => true,

        // Framework integrations
        'inertia' => true,
        'react-query' => true,  // Enable for testing
        'queries' => true,      // Enable for testing
        'mutations' => true,    // Enable for testing
        'swr' => false,
        'rtk-query' => false,
    ],

    'file_names' => [],

    'laravel_types_path' => null,
    'auto_typescript_transform' => true,

    'transformers' => [],
    'collectors' => [
        OybekDaniyarov\LaravelTrpc\Collectors\DefaultRouteCollector::class,
    ],

    'generators' => [
        'typescript' => OybekDaniyarov\LaravelTrpc\Generators\TypeScriptGenerator::class,
    ],

    'type_replacements' => [
        Carbon\Carbon::class => 'string',
        Carbon\CarbonImmutable::class => 'string',
        Illuminate\Support\Carbon::class => 'string',
        DateTimeInterface::class => 'string',
    ],
];
