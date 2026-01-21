<?php

declare(strict_types=1);

namespace OybekDaniyarov\LaravelTrpc\Attributes;

use Attribute;

/**
 * Backwards-compatible alias for TypedRoute attribute.
 *
 * @deprecated Use TypedRoute instead. This alias will be removed in v2.0.
 * @see TypedRoute
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class ApiRoute
{
    /**
     * @param  class-string|null  $request  Request Data class for body (POST/PUT/PATCH)
     * @param  class-string|null  $query  Query Data class for query parameters (GET)
     * @param  class-string|null  $response  Response Data class for the endpoint
     * @param  class-string|null  $errorResponse  Error response Data class (defaults to ValidationError)
     * @param  bool  $isCollection  Whether response is an array of items (Array<T>)
     * @param  bool  $isPaginated  Whether response is paginated (PaginatedResponse<T>)
     */
    public function __construct(
        public ?string $request = null,
        public ?string $query = null,
        public ?string $response = null,
        public ?string $errorResponse = null,
        public bool $isCollection = false,
        public bool $isPaginated = false,
    ) {}
}
