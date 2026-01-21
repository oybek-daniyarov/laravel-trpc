<?php

declare(strict_types=1);

namespace OybekDaniyarov\LaravelTrpc\Data;

/**
 * Data transfer object holding type information for an API route.
 *
 * Contains the extracted request, query, response, and error type information
 * that will be used to generate TypeScript type annotations.
 */
final readonly class RouteTypeInfo
{
    public function __construct(
        public ?string $requestType = null,
        public ?string $queryType = null,
        public ?string $responseType = null,
        public ?string $errorType = null,
        public bool $isCollection = false,
        public bool $isPaginated = false,
        public bool $isRequestNullable = false,
        public bool $isResponseNullable = false,
        public bool $isQueryNullable = false,
    ) {}

    /**
     * Get the TypeScript representation of the response type.
     *
     * Returns 'unknown' if no type is available, wraps in PaginatedResponse<T>
     * for paginated responses, or Array<T> for collections. Appends ' | null'
     * when the response type is nullable.
     */
    public function getTypeScriptResponseType(): string
    {
        if ($this->responseType === null) {
            return $this->isResponseNullable ? 'unknown | null' : 'unknown';
        }

        $type = $this->responseType;

        if ($this->isPaginated) {
            $type = "PaginatedResponse<{$this->responseType}>";
        } elseif ($this->isCollection) {
            $type = "Array<{$this->responseType}>";
        }

        return $this->isResponseNullable ? "{$type} | null" : $type;
    }

    /**
     * Get the TypeScript representation of the request type.
     *
     * Returns 'void' if no request type is available. Appends ' | null'
     * when the request type is nullable.
     */
    public function getTypeScriptRequestType(): string
    {
        if ($this->requestType === null) {
            return 'void';
        }

        return $this->isRequestNullable ? "{$this->requestType} | null" : $this->requestType;
    }

    /**
     * Get the TypeScript representation of the query type.
     *
     * Returns null if no query type is available. Appends ' | null'
     * when the query type is nullable.
     */
    public function getTypeScriptQueryType(): ?string
    {
        if ($this->queryType === null) {
            return null;
        }

        return $this->isQueryNullable ? "{$this->queryType} | null" : $this->queryType;
    }

    /**
     * Get the TypeScript representation of the error type.
     *
     * Returns null if no error type is available (defaults to ValidationError in templates).
     */
    public function getTypeScriptErrorType(): ?string
    {
        return $this->errorType;
    }
}
