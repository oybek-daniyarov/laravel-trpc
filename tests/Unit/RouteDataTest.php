<?php

declare(strict_types=1);

use OybekDaniyarov\LaravelTrpc\Data\RouteData;

it('can be created with required properties', function () {
    $route = new RouteData(
        method: 'get',
        path: 'api/users',
        name: 'users.index',
        group: 'users',
    );

    expect($route->method)->toBe('get')
        ->and($route->path)->toBe('api/users')
        ->and($route->name)->toBe('users.index')
        ->and($route->group)->toBe('users');
});

it('has correct default values', function () {
    $route = new RouteData(
        method: 'get',
        path: 'api/users',
        name: 'users.index',
        group: 'users',
    );

    expect($route->pathParams)->toBe([])
        ->and($route->requestType)->toBeNull()
        ->and($route->queryType)->toBeNull()
        ->and($route->responseType)->toBeNull()
        ->and($route->hasRequest)->toBeFalse()
        ->and($route->hasQuery)->toBeFalse()
        ->and($route->hasResponse)->toBeFalse()
        ->and($route->isCollection)->toBeFalse()
        ->and($route->isPaginated)->toBeFalse()
        ->and($route->middleware)->toBe([]);
});

it('can be created with all properties', function () {
    $route = new RouteData(
        method: 'post',
        path: 'api/users',
        name: 'users.store',
        group: 'users',
        pathParams: [],
        requestType: 'App.Data.CreateUserPayload',
        queryType: null,
        responseType: 'App.Data.UserResponse',
        hasRequest: true,
        hasQuery: false,
        hasResponse: true,
        isCollection: false,
        isPaginated: false,
        middleware: ['auth:sanctum'],
        requestClass: 'App\\Data\\CreateUserPayload',
        queryClass: null,
    );

    expect($route->requestType)->toBe('App.Data.CreateUserPayload')
        ->and($route->responseType)->toBe('App.Data.UserResponse')
        ->and($route->hasRequest)->toBeTrue()
        ->and($route->hasResponse)->toBeTrue()
        ->and($route->middleware)->toBe(['auth:sanctum']);
});

it('can convert to array', function () {
    $route = new RouteData(
        method: 'get',
        path: 'api/users/{id}',
        name: 'users.show',
        group: 'users',
        pathParams: ['id'],
    );

    $array = $route->toArray();

    expect($array)->toBeArray()
        ->and($array['method'])->toBe('get')
        ->and($array['path'])->toBe('api/users/{id}')
        ->and($array['name'])->toBe('users.show')
        ->and($array['pathParams'])->toBe(['id']);
});
