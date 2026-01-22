<?php

declare(strict_types=1);

namespace OybekDaniyarov\LaravelTrpc;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use OybekDaniyarov\LaravelTrpc\Commands\GenerateTrpcCommand;
use OybekDaniyarov\LaravelTrpc\Services\MiddlewareProcessor;
use OybekDaniyarov\LaravelTrpc\Services\RouteTypeExtractor;
use OybekDaniyarov\LaravelTrpc\Services\StubRenderer;

final class TrpcServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/trpc.php', 'trpc');

        $this->app->singleton(TrpcConfig::class, function () {
            return TrpcConfig::fromConfig();
        });

        $this->app->singleton(StubRenderer::class);
        $this->app->singleton(RouteTypeExtractor::class);
        $this->app->singleton(MiddlewareProcessor::class);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/stubs', 'trpc');

        // Register custom blade extensions for TypeScript files
        /** @var \Illuminate\View\Factory $view */
        $view = $this->app->make('view');
        $view->addExtension('blade.ts', 'blade');
        $view->addExtension('blade.md', 'blade');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/trpc.php' => config_path('trpc.php'),
            ], 'trpc-config');

            $this->publishes([
                __DIR__.'/../resources/stubs' => resource_path('views/vendor/trpc'),
            ], 'trpc-stubs');

            $this->commands([
                GenerateTrpcCommand::class,
            ]);
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array<int, class-string>
     */
    public function provides(): array
    {
        return [
            TrpcConfig::class,
            StubRenderer::class,
            RouteTypeExtractor::class,
            MiddlewareProcessor::class,
        ];
    }
}
