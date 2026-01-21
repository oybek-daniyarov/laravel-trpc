<?php

declare(strict_types=1);

namespace OybekDaniyarov\LaravelTrpc\Attributes;

use Attribute;

/**
 * Attribute for explicitly declaring request and response types on API controller methods.
 *
 * When applied to a controller method, this attribute takes priority over static analysis
 * for determining the request and response types in the generated TypeScript definitions.
 *
 * @example
 * ```php
 * #[TypedRoute(request: LoginPayload::class, response: AuthResponseData::class)]
 * public function login(LoginPayload $payload): JsonResponse { }
 *
 * #[TypedRoute(query: InspectionFilterPayload::class, response: InspectionResponseData::class, isPaginated: true)]
 * public function index(InspectionFilterPayload $filters): JsonResponse { }
 *
 * #[TypedRoute(response: UserResponseData::class, isCollection: true)]
 * public function list(): JsonResponse { }
 *
 * #[TypedRoute(response: BookingResponseData::class, isPaginated: true)]
 * public function paginated(): JsonResponse { }
 * ```
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class TypedRoute
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
