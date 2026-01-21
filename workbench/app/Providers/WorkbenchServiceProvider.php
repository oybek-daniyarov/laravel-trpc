<?php

declare(strict_types=1);

namespace Workbench\App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

final class WorkbenchServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Merge workbench config
        $this->mergeConfigFrom(
            __DIR__.'/../../config/trpc.php',
            'trpc'
        );
    }

    public function boot(): void
    {
        Route::middleware('api')
            ->group(__DIR__.'/../../routes/api.php');
    }
}
