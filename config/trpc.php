<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Laravel tRPC Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration options for the Laravel tRPC generator
    | that creates TypeScript definitions and Postman collections from your
    | Laravel API routes.
    |
    */

    // Output directory for generated TypeScript files
    'output_path' => env('TRPC_OUTPUT_PATH', resource_path('js/api')),

    // API route prefix to filter routes (used in 'api' and 'web' route modes)
    'api_prefix' => env('TRPC_PREFIX', 'api'),

    // API version to include (e.g., 'v1', 'v2')
    'version' => env('TRPC_VERSION', 'v1'),

    /*
    |--------------------------------------------------------------------------
    | Route Collection Mode
    |--------------------------------------------------------------------------
    |
    | Controls which routes are collected for TypeScript generation:
    | - 'api': Only routes starting with api_prefix (default)
    | - 'web': Only routes NOT starting with api_prefix
    | - 'all': All routes (use with include/exclude patterns)
    | - 'named': Only routes with names
    | - 'attributed': Only routes with #[TypedRoute] attribute
    |
    */

    'route_mode' => env('TRPC_ROUTE_MODE', 'api'),

    /*
    |--------------------------------------------------------------------------
    | Route Include Patterns
    |--------------------------------------------------------------------------
    |
    | If specified, only routes matching these patterns will be included.
    | Patterns can match route names (dot notation) or URIs (path notation).
    | Examples: 'users.*', 'api/v1/*', 'login', 'auth.*'
    |
    */

    'include_patterns' => [
        // 'login.*',
        // 'register.*',
        // 'api/*',
    ],

    /*
    |--------------------------------------------------------------------------
    | Route Exclude Patterns
    |--------------------------------------------------------------------------
    |
    | Routes matching these patterns will always be excluded.
    | Applied after route mode filtering.
    |
    */

    'exclude_patterns' => [
        'boost.*',
        'debugbar.*',
        'horizon.*',
        'telescope.*',
        'sanctum.*',
        'ignition.*',
    ],

    // HTTP methods to exclude from generation
    'exclude_methods' => [
        'options',
        'head',
    ],

    // Custom route name mappings
    'route_name_mappings' => [
        // 'api/v1/users/{user}' => 'users.show',
        // 'api/v1/posts/{post}/comments' => 'posts.comments.index',
    ],

    // Route groups to organize generated types
    'route_groups' => [
        'auth' => ['login', 'register', 'logout', 'password', 'auth'],
        'users' => ['users', 'profile'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Framework Preset
    |--------------------------------------------------------------------------
    |
    | Quick configuration for common setups. Set to null to use 'outputs' directly.
    | - 'inertia': Core files + Inertia helpers (recommended for Inertia apps)
    | - 'api': Core files + React Query (for API-first SPAs)
    | - 'spa': Core files + Inertia + React Query (hybrid apps)
    |
    */

    'preset' => null, // 'inertia', 'api', 'spa', or null

    /*
    |--------------------------------------------------------------------------
    | Output Files
    |--------------------------------------------------------------------------
    |
    | Control which TypeScript files are generated. Ignored if preset is set.
    |
    */

    'outputs' => [
        // Core files (recommended to keep enabled)
        'routes' => true,
        'types' => true,
        'helpers' => true,
        'url-builder' => true,
        'fetch' => true,
        'client' => true,
        'index' => true,
        'readme' => true,

        // Grouped API client (object-based: api.users.show())
        'grouped-api' => true,   // Generates api.ts with nested endpoint objects

        // Framework integrations
        'inertia' => true,       // Inertia.js helpers (route, visit, formAction)
        'react-query' => false,  // TanStack React Query integration utilities
        'queries' => false,      // React Query hooks per resource (requires grouped-api)
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom File Names
    |--------------------------------------------------------------------------
    |
    | Override the default output file names if needed.
    |
    */

    'file_names' => [
        // 'routes' => 'route-map.ts',
        // 'types' => 'api-types.ts',
        // 'helpers' => 'type-helpers.ts',
    ],

    /*
    |--------------------------------------------------------------------------
    | Laravel Types Path
    |--------------------------------------------------------------------------
    |
    | Path to the laravel.d.ts file generated by spatie/typescript-transformer.
    | Set to null to auto-detect from typescript-transformer config.
    |
    */

    'laravel_types_path' => null,

    /*
    |--------------------------------------------------------------------------
    | Auto TypeScript Transform
    |--------------------------------------------------------------------------
    |
    | Automatically run `php artisan typescript:transform` before generating
    | Laravel tRPC output if laravel.d.ts doesn't exist.
    |
    */

    'auto_typescript_transform' => true,

    /*
    |--------------------------------------------------------------------------
    | Extensibility: Custom Transformers
    |--------------------------------------------------------------------------
    |
    | Register custom type transformers for specific PHP types. These will
    | override the default type transformation behavior.
    |
    | Example:
    | \App\ValueObjects\Money::class => \App\LaravelTrpc\MoneyTransformer::class,
    |
    */

    'transformers' => [
        // SomeType::class => CustomTransformer::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Extensibility: Custom Collectors
    |--------------------------------------------------------------------------
    |
    | Register custom route collectors for discovering routes. The default
    | collector uses Laravel's route collection.
    |
    */

    'collectors' => [
        OybekDaniyarov\LaravelTrpc\Collectors\DefaultRouteCollector::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Extensibility: Output Generators
    |--------------------------------------------------------------------------
    |
    | Register output generators. By default, TypeScript and Postman generators
    | are included. You can add custom generators for other formats.
    |
    */

    'generators' => [
        'typescript' => OybekDaniyarov\LaravelTrpc\Generators\TypeScriptGenerator::class,
        'postman' => OybekDaniyarov\LaravelTrpc\Generators\PostmanGenerator::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Type Replacements
    |--------------------------------------------------------------------------
    |
    | PHP types that should be replaced with specific TypeScript types.
    | Similar to spatie/typescript-transformer's type_replacements.
    |
    */

    'type_replacements' => [
        Carbon\Carbon::class => 'string',
        Carbon\CarbonImmutable::class => 'string',
        Illuminate\Support\Carbon::class => 'string',
        DateTimeInterface::class => 'string',
    ],

    /*
    |--------------------------------------------------------------------------
    | Postman Collection Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration options for the Postman collection generator.
    |
    */

    'postman' => [
        // Output path for generated Postman collection
        'output_path' => storage_path('app/postman'),

        // Collection name displayed in Postman
        'collection_name' => env('APP_NAME', 'API').' Collection',

        // Base URL variable used in requests (uses Postman variable syntax)
        'base_url' => '{{base_url}}',

        // Authentication type: 'bearer', 'apikey', or null
        'auth_type' => 'bearer',

        // Default headers added to all requests
        'default_headers' => [
            // 'X-Custom-Header' => 'value',
        ],
    ],
];
