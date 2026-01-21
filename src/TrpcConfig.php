<?php

declare(strict_types=1);

namespace OybekDaniyarov\LaravelTrpc;

use DateTimeInterface;
use InvalidArgumentException;

/**
 * Configuration object for Laravel tRPC.
 *
 * This class provides a strongly-typed interface to the package configuration,
 * with caching for frequently accessed values.
 */
final class TrpcConfig
{
    public const string DEFAULT_API_PREFIX = 'api';

    /** @var array<string, mixed> */
    private readonly array $config;

    /** @var array<string, mixed> */
    private array $cache = [];

    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    /**
     * Alias for getOutputPath() for convenience.
     */
    public function __get(string $name): mixed
    {
        return match ($name) {
            'outputPath' => $this->getOutputPath(),
            'apiPrefix' => $this->getApiPrefix(),
            'version' => $this->getVersion(),
            'excludeMethods' => $this->getExcludeMethods(),
            'routeGroups' => $this->getRouteGroups(),
            'transformers' => $this->getTransformers(),
            'collectors' => $this->getCollectors(),
            'generators' => $this->getGenerators(),
            'typeReplacements' => $this->getTypeReplacements(),
            'postmanOutputPath' => $this->getPostmanOutputPath(),
            'postmanCollectionName' => $this->getPostmanCollectionName(),
            'postmanBaseUrl' => $this->getPostmanBaseUrl(),
            'postmanAuthType' => $this->getPostmanAuthType(),
            default => null,
        };
    }

    /**
     * Create from Laravel config with validation.
     *
     * @throws InvalidArgumentException If configuration is invalid
     */
    public static function fromConfig(): self
    {
        /** @var array<string, mixed> $config */
        $config = config('trpc', []);

        self::validateConfig($config);

        return new self($config);
    }

    /**
     * Validate the configuration array.
     *
     * @param  array<string, mixed>  $config
     *
     * @throws InvalidArgumentException
     */
    private static function validateConfig(array $config): void
    {
        // Validate output_path if provided
        if (isset($config['output_path'])) {
            if (! is_string($config['output_path'])) {
                throw new InvalidArgumentException('trpc.output_path must be a string');
            }

            $parentDir = dirname($config['output_path']);
            if (! is_dir($parentDir) && ! @mkdir($parentDir, 0755, true)) {
                throw new InvalidArgumentException(
                    "trpc.output_path parent directory does not exist and could not be created: {$parentDir}"
                );
            }
        }

        // Validate route_mode if provided
        $validModes = ['all', 'api', 'web', 'named', 'attributed'];
        if (isset($config['route_mode']) && ! in_array($config['route_mode'], $validModes, true)) {
            throw new InvalidArgumentException(
                'trpc.route_mode must be one of: '.implode(', ', $validModes)
            );
        }

        // Validate api_prefix if provided
        if (isset($config['api_prefix']) && ! is_string($config['api_prefix'])) {
            throw new InvalidArgumentException('trpc.api_prefix must be a string');
        }

        // Validate collectors if provided
        if (isset($config['collectors'])) {
            if (! is_array($config['collectors'])) {
                throw new InvalidArgumentException('trpc.collectors must be an array');
            }

            foreach ($config['collectors'] as $collector) {
                if (! is_string($collector) || ! class_exists($collector)) {
                    throw new InvalidArgumentException(
                        "trpc.collectors contains invalid class: {$collector}"
                    );
                }
            }
        }

        // Validate generators if provided
        if (isset($config['generators'])) {
            if (! is_array($config['generators'])) {
                throw new InvalidArgumentException('trpc.generators must be an array');
            }

            foreach ($config['generators'] as $name => $generator) {
                if (! is_string($generator) || ! class_exists($generator)) {
                    throw new InvalidArgumentException(
                        "trpc.generators.{$name} contains invalid class: {$generator}"
                    );
                }
            }
        }
    }

    /**
     * Get the output path for generated TypeScript files.
     */
    public function getOutputPath(): string
    {
        return $this->get('output_path', resource_path('js/api'));
    }

    /**
     * Get the API route prefix.
     */
    public function getApiPrefix(): string
    {
        return $this->get('api_prefix', self::DEFAULT_API_PREFIX);
    }

    /**
     * Get the API version.
     */
    public function getVersion(): string
    {
        return $this->get('version', 'v1');
    }

    /**
     * Get HTTP methods to exclude.
     *
     * @return array<int, string>
     */
    public function getExcludeMethods(): array
    {
        return $this->get('exclude_methods', ['options', 'head']);
    }

    /**
     * Get route name mappings.
     *
     * @return array<string, string>
     */
    public function getRouteNameMappings(): array
    {
        return $this->get('route_name_mappings', []);
    }

    /**
     * Get route groups configuration.
     *
     * @return array<string, array<int, string>>
     */
    public function getRouteGroups(): array
    {
        return $this->get('route_groups', []);
    }

    /**
     * Get route collection mode.
     *
     * @return string One of: 'all', 'api', 'web', 'named', 'attributed'
     */
    public function getRouteMode(): string
    {
        return $this->get('route_mode', 'api');
    }

    /**
     * Get route include patterns.
     *
     * @return array<int, string>
     */
    public function getIncludePatterns(): array
    {
        return $this->get('include_patterns', []);
    }

    /**
     * Get route exclude patterns.
     *
     * @return array<int, string>
     */
    public function getExcludePatterns(): array
    {
        return $this->get('exclude_patterns', [
            'debugbar.*',
            'horizon.*',
            'telescope.*',
            'sanctum.*',
        ]);
    }

    /**
     * Get output file configuration.
     *
     * @return array<string, bool>
     */
    public function getOutputs(): array
    {
        return $this->get('outputs', [
            'routes' => true,
            'types' => true,
            'helpers' => true,
            'url-builder' => true,
            'fetch' => true,
            'client' => true,
            'index' => true,
            'readme' => true,
            'inertia' => true,
            'react-query' => false,
        ]);
    }

    /**
     * Check if a specific output file should be generated.
     */
    public function shouldGenerateOutput(string $name): bool
    {
        $outputs = $this->getOutputs();

        return $outputs[$name] ?? false;
    }

    /**
     * Get framework preset.
     *
     * @return string|null One of: 'inertia', 'api', 'spa', or null for custom
     */
    public function getPreset(): ?string
    {
        return $this->get('preset');
    }

    /**
     * Get effective outputs based on preset or direct config.
     *
     * @return array<string, bool>
     */
    public function getEffectiveOutputs(): array
    {
        $preset = $this->getPreset();

        if ($preset === null) {
            return $this->getOutputs();
        }

        $baseOutputs = [
            'routes' => true,
            'types' => true,
            'helpers' => true,
            'url-builder' => true,
            'fetch' => true,
            'client' => true,
            'index' => true,
            'readme' => true,
            'inertia' => false,
            'react-query' => false,
        ];

        return match ($preset) {
            'inertia' => array_merge($baseOutputs, ['inertia' => true]),
            'api' => array_merge($baseOutputs, ['react-query' => true]),
            'spa' => array_merge($baseOutputs, ['inertia' => true, 'react-query' => true]),
            default => $this->getOutputs(),
        };
    }

    /**
     * Get Laravel types path (laravel.d.ts location).
     * Auto-detects from Spatie typescript-transformer config if not specified.
     */
    public function getLaravelTypesPath(): ?string
    {
        $configured = $this->get('laravel_types_path');

        if ($configured !== null) {
            return $configured;
        }

        // Auto-detect from Spatie config
        $spatieOutput = config('typescript-transformer.output_file');

        if ($spatieOutput) {
            return dirname($spatieOutput);
        }

        return null;
    }

    /**
     * Get custom output file names.
     *
     * @return array<string, string>
     */
    public function getFileNames(): array
    {
        return $this->get('file_names', []);
    }

    /**
     * Get the filename for a specific output, allowing customization.
     */
    public function getFileName(string $key, string $default): string
    {
        $fileNames = $this->getFileNames();

        return $fileNames[$key] ?? $default;
    }

    /**
     * Whether to auto-run Spatie typescript:transform before generating.
     */
    public function shouldAutoTransform(): bool
    {
        return $this->get('auto_typescript_transform', true);
    }

    /**
     * Get custom transformers.
     *
     * @return array<class-string, class-string>
     */
    public function getTransformers(): array
    {
        return $this->get('transformers', []);
    }

    /**
     * Get route collectors.
     *
     * @return array<int, class-string>
     */
    public function getCollectors(): array
    {
        return $this->get('collectors', [
            Collectors\DefaultRouteCollector::class,
        ]);
    }

    /**
     * Get output generators.
     *
     * @return array<string, class-string>
     */
    public function getGenerators(): array
    {
        return $this->get('generators', [
            'typescript' => Generators\TypeScriptGenerator::class,
            'postman' => Generators\PostmanGenerator::class,
        ]);
    }

    /**
     * Get type replacements.
     *
     * @return array<class-string, string>
     */
    public function getTypeReplacements(): array
    {
        return $this->get('type_replacements', [
            \Carbon\Carbon::class => 'string',
            \Carbon\CarbonImmutable::class => 'string',
            \Illuminate\Support\Carbon::class => 'string',
            DateTimeInterface::class => 'string',
        ]);
    }

    /**
     * Get Postman output path.
     */
    public function getPostmanOutputPath(): string
    {
        return $this->get('postman.output_path', storage_path('app/postman'));
    }

    /**
     * Get Postman collection name.
     */
    public function getPostmanCollectionName(): string
    {
        return $this->get('postman.collection_name', config('app.name', 'API').' Collection');
    }

    /**
     * Get Postman base URL.
     */
    public function getPostmanBaseUrl(): string
    {
        return $this->get('postman.base_url', '{{base_url}}');
    }

    /**
     * Get Postman auth type.
     */
    public function getPostmanAuthType(): ?string
    {
        return $this->get('postman.auth_type', 'bearer');
    }

    /**
     * Get Postman default headers.
     *
     * @return array<string, string>
     */
    public function getPostmanDefaultHeaders(): array
    {
        return $this->get('postman.default_headers', []);
    }

    /**
     * Get a config value with caching.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        if (isset($this->cache[$key])) {
            return $this->cache[$key];
        }

        $keys = explode('.', $key);
        $value = $this->config;

        foreach ($keys as $k) {
            if (! is_array($value) || ! array_key_exists($k, $value)) {
                return $this->cache[$key] = $default;
            }
            $value = $value[$k];
        }

        return $this->cache[$key] = $value ?? $default;
    }

    /**
     * Get all config values.
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->config;
    }
}
