<?php

declare(strict_types=1);

namespace OybekDaniyarov\LaravelTrpc\Data;

/**
 * Data transfer object representing a collected API route.
 */
final readonly class RouteData
{
    /**
     * @param  array<int, string>  $pathParams
     * @param  array<int, string>  $middleware
     */
    public function __construct(
        public string $method,
        public string $path,
        public string $name,
        public string $group,
        public array $pathParams = [],
        public ?string $requestType = null,
        public ?string $queryType = null,
        public ?string $responseType = null,
        public ?string $errorType = null,
        public bool $hasRequest = false,
        public bool $hasQuery = false,
        public bool $hasResponse = false,
        public bool $isCollection = false,
        public bool $isPaginated = false,
        public array $middleware = [],
        public ?string $requestClass = null,
        public ?string $queryClass = null,
    ) {}

    /**
     * Check if the route requires authentication based on middleware.
     */
    public function isAuthenticated(): bool
    {
        foreach ($this->middleware as $middleware) {
            if (str_starts_with($middleware, 'auth') || $middleware === 'sanctum') {
                return true;
            }
        }

        return false;
    }

    /**
     * Convert to array representation.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'method' => $this->method,
            'path' => $this->path,
            'name' => $this->name,
            'group' => $this->group,
            'pathParams' => $this->pathParams,
            'requestType' => $this->requestType,
            'queryType' => $this->queryType,
            'responseType' => $this->responseType,
            'errorType' => $this->errorType,
            'hasRequest' => $this->hasRequest,
            'hasQuery' => $this->hasQuery,
            'hasResponse' => $this->hasResponse,
            'isCollection' => $this->isCollection,
            'isPaginated' => $this->isPaginated,
            'middleware' => $this->middleware,
            'requestClass' => $this->requestClass,
            'queryClass' => $this->queryClass,
            'isAuthenticated' => $this->isAuthenticated(),
        ];
    }
}
