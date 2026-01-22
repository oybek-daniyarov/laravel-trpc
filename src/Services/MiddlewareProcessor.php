<?php

declare(strict_types=1);

namespace OybekDaniyarov\LaravelTrpc\Services;

use Illuminate\Support\Str;
use OybekDaniyarov\LaravelTrpc\TrpcConfig;

/**
 * Processes middleware arrays by filtering excluded middleware
 * and transforming FQCNs to short names.
 */
final class MiddlewareProcessor
{
    public function __construct(
        private readonly TrpcConfig $config,
    ) {}

    /**
     * Process middleware array: filter excluded patterns and transform to short names.
     *
     * @param  array<int, string>  $middleware
     * @return array<int, string>
     */
    public function process(array $middleware): array
    {
        $excludePatterns = $this->config->getMiddlewareExcludePatterns();
        $useShortNames = $this->config->shouldUseShortMiddlewareNames();

        $result = [];

        foreach ($middleware as $item) {
            // Check if middleware matches any exclude pattern
            if ($this->shouldExclude($item, $excludePatterns)) {
                continue;
            }

            // Transform to short name if enabled
            $result[] = $useShortNames ? $this->toShortName($item) : $item;
        }

        return $result;
    }

    /**
     * Transform FQCN middleware to short name.
     *
     * Examples:
     * - 'Stancl\Tenancy\Middleware\InitializeTenancyByDomain' => 'InitializeTenancyByDomain'
     * - 'App\Http\Middleware\RateLimiter:api' => 'RateLimiter:api'
     * - 'auth:sanctum' => 'auth:sanctum' (unchanged)
     * - 'web' => 'web' (unchanged)
     */
    public function toShortName(string $middleware): string
    {
        // Check if it has parameters (e.g., 'auth:sanctum' or 'RateLimiter:api')
        $parts = explode(':', $middleware, 2);
        $className = $parts[0];
        $parameters = $parts[1] ?? null;

        // If it doesn't look like a FQCN (no backslashes), return as-is
        if (! str_contains($className, '\\')) {
            return $middleware;
        }

        // Extract the class name from FQCN
        $shortName = class_basename($className);

        // Re-attach parameters if present
        return $parameters !== null ? "{$shortName}:{$parameters}" : $shortName;
    }

    /**
     * Check if middleware should be excluded based on patterns.
     *
     * @param  array<int, string>  $patterns
     */
    private function shouldExclude(string $middleware, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            if (Str::is($pattern, $middleware)) {
                return true;
            }
        }

        return false;
    }
}
