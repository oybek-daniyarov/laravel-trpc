<?php

declare(strict_types=1);

namespace OybekDaniyarov\LaravelTrpc\Services;

use Illuminate\Support\Facades\View;

/**
 * Service for rendering Blade stub templates.
 *
 * Provides a clean interface for rendering TypeScript files from Blade templates.
 */
final class StubRenderer
{
    /**
     * Render a stub template with the given data.
     *
     * @param  array<string, mixed>  $data
     */
    public function render(string $template, array $data = []): string
    {
        return View::make("trpc::{$template}", $data)->render();
    }

    /**
     * Check if a stub template exists.
     */
    public function exists(string $template): bool
    {
        return View::exists("trpc::{$template}");
    }

    /**
     * Render a partial template.
     *
     * @param  array<string, mixed>  $data
     */
    public function partial(string $template, array $data = []): string
    {
        return $this->render("partials.{$template}", $data);
    }
}
