<?php

declare(strict_types=1);

use OybekDaniyarov\LaravelTrpc\Attributes\ApiRoute;
use OybekDaniyarov\LaravelTrpc\Attributes\TypedRoute;

it('creates attribute with request class', function () {
    $attribute = new TypedRoute(
        request: 'App\\Data\\UserData',
    );

    expect($attribute->request)->toBe('App\\Data\\UserData')
        ->and($attribute->query)->toBeNull()
        ->and($attribute->response)->toBeNull();
});

it('creates attribute with query class', function () {
    $attribute = new TypedRoute(
        query: 'App\\Data\\FilterData',
    );

    expect($attribute->query)->toBe('App\\Data\\FilterData')
        ->and($attribute->request)->toBeNull()
        ->and($attribute->response)->toBeNull();
});

it('creates attribute with response class', function () {
    $attribute = new TypedRoute(
        response: 'App\\Data\\UserResponseData',
    );

    expect($attribute->response)->toBe('App\\Data\\UserResponseData')
        ->and($attribute->request)->toBeNull()
        ->and($attribute->query)->toBeNull();
});

it('creates attribute with all parameters', function () {
    $attribute = new TypedRoute(
        request: 'App\\Data\\CreateUserData',
        query: 'App\\Data\\QueryData',
        response: 'App\\Data\\UserResponseData',
        isCollection: true,
        isPaginated: true,
    );

    expect($attribute->request)->toBe('App\\Data\\CreateUserData')
        ->and($attribute->query)->toBe('App\\Data\\QueryData')
        ->and($attribute->response)->toBe('App\\Data\\UserResponseData')
        ->and($attribute->isCollection)->toBeTrue()
        ->and($attribute->isPaginated)->toBeTrue();
});

it('isPaginated defaults to false', function () {
    $attribute = new TypedRoute;

    expect($attribute->isPaginated)->toBeFalse();
});

it('isCollection defaults to false', function () {
    $attribute = new TypedRoute;

    expect($attribute->isCollection)->toBeFalse();
});

it('all parameters are nullable', function () {
    $attribute = new TypedRoute;

    expect($attribute->request)->toBeNull()
        ->and($attribute->query)->toBeNull()
        ->and($attribute->response)->toBeNull();
});

it('can be used as method attribute', function () {
    $reflection = new ReflectionClass(TypedRoute::class);
    $attributes = $reflection->getAttributes();

    // TypedRoute should have the Attribute attribute
    expect($reflection->getAttributes(Attribute::class))->not->toBeEmpty();
});

it('ApiRoute is backwards compatible alias', function () {
    $attribute = new ApiRoute(
        request: 'App\\Data\\UserData',
        response: 'App\\Data\\ResponseData',
    );

    expect($attribute->request)->toBe('App\\Data\\UserData')
        ->and($attribute->response)->toBe('App\\Data\\ResponseData')
        ->and($attribute->isCollection)->toBeFalse()
        ->and($attribute->isPaginated)->toBeFalse();
});

it('TypedRoute and ApiRoute have same properties', function () {
    $typedRoute = new TypedRoute(
        request: 'Request',
        query: 'Query',
        response: 'Response',
        isCollection: true,
        isPaginated: true,
    );

    $apiRoute = new ApiRoute(
        request: 'Request',
        query: 'Query',
        response: 'Response',
        isCollection: true,
        isPaginated: true,
    );

    expect($typedRoute->request)->toBe($apiRoute->request)
        ->and($typedRoute->query)->toBe($apiRoute->query)
        ->and($typedRoute->response)->toBe($apiRoute->response)
        ->and($typedRoute->isCollection)->toBe($apiRoute->isCollection)
        ->and($typedRoute->isPaginated)->toBe($apiRoute->isPaginated);
});

it('handles paginated response configuration', function () {
    $attribute = new TypedRoute(
        response: 'App\\Data\\UserData',
        isPaginated: true,
    );

    expect($attribute->isPaginated)->toBeTrue()
        ->and($attribute->isCollection)->toBeFalse();
});

it('handles collection response configuration', function () {
    $attribute = new TypedRoute(
        response: 'App\\Data\\UserData',
        isCollection: true,
    );

    expect($attribute->isCollection)->toBeTrue()
        ->and($attribute->isPaginated)->toBeFalse();
});
