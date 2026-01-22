<?php

declare(strict_types=1);

namespace OybekDaniyarov\LaravelTrpc\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use OybekDaniyarov\LaravelTrpc\Collections\RouteCollection;
use OybekDaniyarov\LaravelTrpc\Contracts\Collector;
use OybekDaniyarov\LaravelTrpc\Data\Context\GeneratorContext;
use OybekDaniyarov\LaravelTrpc\Data\GeneratorResult;
use OybekDaniyarov\LaravelTrpc\Generators\PostmanGenerator;
use OybekDaniyarov\LaravelTrpc\Generators\TypeScriptGenerator;
use OybekDaniyarov\LaravelTrpc\TrpcConfig;

use function Laravel\Prompts\progress;

/**
 * Artisan command to generate TypeScript definitions and Postman collections.
 *
 * @example
 * ```bash
 * # Generate TypeScript definitions (default)
 * php artisan trpc:generate
 *
 * # Generate Postman collection only
 * php artisan trpc:generate --postman
 * php artisan trpc:generate --format=postman
 *
 * # Generate both TypeScript and Postman
 * php artisan trpc:generate --format=all
 *
 * # Generate Postman collection with environment file
 * php artisan trpc:generate --postman --postman-env
 * ```
 */
final class GenerateTrpcCommand extends Command
{
    protected $signature = 'trpc:generate
                            {--output= : Output directory path (overrides config)}
                            {--api-prefix= : API route prefix to filter (overrides config)}
                            {--skip-typescript-transform : Skip running typescript:transform first}
                            {--postman : Generate Postman collection only (shorthand for --format=postman)}
                            {--postman-env : Also generate Postman environment file}
                            {--format=typescript : Output format (typescript, postman, all)}
                            {--force : Overwrite files without confirmation}
                            {--base-url= : Base URL for the generated client (defaults to empty)}
                            {--clean : Clear output directory before generating}';

    protected $description = 'Generate TypeScript definitions and/or Postman collections from API routes';

    public function __construct(
        private readonly TrpcConfig $config,
        private readonly TypeScriptGenerator $typeScriptGenerator,
        private readonly PostmanGenerator $postmanGenerator,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Generating API definitions...');

        try {
            // Collect routes
            $routes = $this->collectRoutes();

            if ($routes->isEmpty()) {
                $this->warn('No API routes found matching the criteria.');

                return self::SUCCESS;
            }

            $this->displayRouteStats($routes);

            // Determine what to generate
            $generateTypeScript = $this->shouldGenerateTypeScript();
            $generatePostman = $this->shouldGeneratePostman();
            $cancelled = false;

            if ($generateTypeScript) {
                if (! $this->generateTypeScript($routes)) {
                    $cancelled = true;
                }
            }

            if ($generatePostman && ! $cancelled) {
                if (! $this->generatePostman($routes)) {
                    $cancelled = true;
                }
            }

            if (! $cancelled) {
                $this->info('Generation completed successfully!');
            }

            return self::SUCCESS;
        } catch (Exception $e) {
            $this->error('Error generating API definitions: '.$e->getMessage());

            return self::FAILURE;
        }
    }

    private function collectRoutes(): RouteCollection
    {
        $routes = new RouteCollection;

        foreach ($this->config->getCollectors() as $collectorClass) {
            /** @var Collector $collector */
            $collector = app($collectorClass, ['config' => $this->getEffectiveConfig()]);
            $routes = $routes->merge($collector->collect());
        }

        return $routes->sortByName();
    }

    private function displayRouteStats(RouteCollection $routes): void
    {
        $withRequestType = 0;
        $withQueryType = 0;
        $withResponseType = 0;
        $warnings = [];

        foreach ($routes as $route) {
            if ($route->hasRequest) {
                $withRequestType++;
            }
            if ($route->hasQuery) {
                $withQueryType++;
            }
            if ($route->hasResponse) {
                $withResponseType++;
            }

            // Collect warnings for routes with potential issues
            $this->collectRouteWarnings($route, $warnings);
        }

        $this->info(sprintf('Found %d API routes', $routes->count()));
        $this->info(sprintf('  - %d with request types', $withRequestType));
        $this->info(sprintf('  - %d with query types', $withQueryType));
        $this->info(sprintf('  - %d with response types', $withResponseType));

        // Display warnings
        if (! empty($warnings)) {
            $this->newLine();
            foreach ($warnings as $warning) {
                $this->warn($warning);
            }
        }

        $this->newLine();
        $this->line(sprintf(
            '<fg=green>✓</> Generated %d routes (%d with request types, %d with response types)',
            $routes->count(),
            $withRequestType,
            $withResponseType
        ));
    }

    /**
     * Collect validation warnings for a route.
     *
     * @param  array<int, string>  $warnings
     */
    private function collectRouteWarnings(\OybekDaniyarov\LaravelTrpc\Data\RouteData $route, array &$warnings): void
    {
        // Check for routes without any type information
        if (! $route->hasRequest && ! $route->hasQuery && ! $route->hasResponse) {
            // Only warn for non-GET routes without request types (GET routes may legitimately have no body)
            if ($route->method !== 'get' && in_array($route->method, ['post', 'put', 'patch'], true)) {
                $warnings[] = sprintf(
                    '<fg=yellow>⚠</> Route \'%s\' (%s %s) has no request type - consider adding #[TypedRoute]',
                    $route->name,
                    mb_strtoupper($route->method),
                    $route->path
                );
            }
        }

        // Check if type classes reference potentially missing types
        if ($route->requestClass !== null && ! $this->isLikelyTransformed($route->requestClass)) {
            $warnings[] = sprintf(
                '<fg=yellow>⚠</> Route \'%s\' uses %s - ensure it has #[TypeScript] attribute',
                $route->name,
                class_basename($route->requestClass)
            );
        }
    }

    /**
     * Check if a class is likely to be transformed by Spatie.
     */
    private function isLikelyTransformed(string $className): bool
    {
        // Most Data classes should be transformed
        // We can't check laravel.d.ts directly here, but we can check for common patterns
        if (! class_exists($className)) {
            return false;
        }

        // Check if class has #[TypeScript] attribute from Spatie
        try {
            $reflection = new \ReflectionClass($className);
            $attributes = $reflection->getAttributes();

            foreach ($attributes as $attribute) {
                if (str_contains($attribute->getName(), 'TypeScript')) {
                    return true;
                }
            }

            // Also check parent class (Data classes often extend Spatie\LaravelData\Data)
            $parent = $reflection->getParentClass();
            if ($parent && str_contains($parent->getName(), 'Data')) {
                return true;
            }
        } catch (\ReflectionException) {
            // Ignore reflection errors
        }

        return true; // Assume it's fine if we can't determine
    }

    private function shouldGenerateTypeScript(): bool
    {
        if ($this->option('format') === 'all') {
            return true;
        }

        if ($this->option('postman') || $this->option('format') === 'postman') {
            return false;
        }

        return $this->option('format') === 'typescript' || ! $this->option('format');
    }

    private function shouldGeneratePostman(): bool
    {
        return $this->option('postman')
            || $this->option('format') === 'postman'
            || $this->option('format') === 'all';
    }

    private function generateTypeScript(RouteCollection $routes): bool
    {
        $outputPath = $this->getTypeScriptOutputPath();

        // Clear output directory if --clean flag is set
        if ($this->option('clean') && File::exists($outputPath)) {
            File::deleteDirectory($outputPath);
            $this->info('Cleared output directory');
        }

        // Check if laravel.d.ts exists and auto-transform is enabled
        $laravelTypesPath = $this->config->getLaravelTypesPath() ?? $outputPath;
        $laravelDtsPath = $laravelTypesPath.'/laravel.d.ts';

        $shouldAutoTransform = $this->config->shouldAutoTransform()
            && ! $this->option('skip-typescript-transform')
            && ! File::exists($laravelDtsPath);

        if ($shouldAutoTransform) {
            $this->warn('laravel.d.ts not found. Running typescript:transform first...');
            $this->call('typescript:transform');
        }

        $baseUrl = $this->option('base-url');

        $context = new GeneratorContext(
            outputPath: $outputPath,
            config: $this->config,
            baseUrl: is_string($baseUrl) ? $baseUrl : null,
        );

        $result = $this->typeScriptGenerator->generate($routes, $context);

        if (! $this->writeFiles($outputPath, $result)) {
            return false;
        }

        $this->info('TypeScript definitions generated!');
        $this->info(sprintf('Output: %s/', $outputPath));
        $this->displayFileTable($result);

        return true;
    }

    private function generatePostman(RouteCollection $routes): bool
    {
        $outputPath = $this->config->getPostmanOutputPath();

        $context = new GeneratorContext(
            outputPath: $outputPath,
            config: $this->config,
            postman: true,
            postmanEnv: (bool) $this->option('postman-env'),
        );

        $result = $this->postmanGenerator->generate($routes, $context);

        if (! $this->writeFiles($outputPath, $result)) {
            return false;
        }

        $this->info('Postman collection generated!');
        $this->info(sprintf('Output: %s/', $outputPath));
        $this->displayFileTable($result);

        return true;
    }

    private function getTypeScriptOutputPath(): string
    {
        $output = $this->option('output');
        if (is_string($output) && $output !== '') {
            return $output;
        }

        return $this->config->getOutputPath();
    }

    private function getEffectiveConfig(): TrpcConfig
    {
        $configArray = $this->config->all();

        if ($this->option('output')) {
            $configArray['output_path'] = $this->option('output');
        }

        if ($this->option('api-prefix')) {
            $configArray['api_prefix'] = $this->option('api-prefix');
        }

        return new TrpcConfig($configArray);
    }

    private function writeFiles(string $outputPath, GeneratorResult $result): bool
    {
        if (! File::exists($outputPath)) {
            File::makeDirectory($outputPath, 0755, true);
        }

        // Check for existing files and confirm overwrite if not --force
        if (! $this->option('force')) {
            $existingFiles = collect($result->files)
                ->keys()
                ->filter(fn (string $f) => File::exists($outputPath.'/'.$f));

            if ($existingFiles->isNotEmpty()) {
                $this->warn('The following files will be overwritten:');
                $existingFiles->each(fn (string $f) => $this->line("  - {$f}"));

                if (! $this->confirm('Do you want to continue?', true)) {
                    $this->info('Generation cancelled.');

                    return false;
                }
            }
        }

        // Convert to array of [filename, content] pairs for progress iteration
        $filePairs = [];
        foreach ($result->files as $filename => $content) {
            $filePairs[] = ['filename' => $filename, 'content' => $content];
        }

        // Write files with progress bar
        progress(
            label: 'Writing files',
            steps: $filePairs,
            callback: function (array $file) use ($outputPath): void {
                $filePath = $outputPath.'/'.$file['filename'];
                $dir = dirname($filePath);

                // Create subdirectories if they don't exist
                if (! File::exists($dir)) {
                    File::makeDirectory($dir, 0755, true);
                }

                File::put($filePath, $file['content']);
            }
        );

        return true;
    }

    private function displayFileTable(GeneratorResult $result): void
    {
        $rows = [];
        foreach ($result->files as $filename => $content) {
            $rows[] = [$filename, $this->formatBytes(mb_strlen($content))];
        }

        $this->table(['File', 'Size'], $rows);
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes.' B';
        }

        return round($bytes / 1024, 1).' KB';
    }
}
