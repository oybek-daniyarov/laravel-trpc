<?php

declare(strict_types=1);

use OybekDaniyarov\LaravelTrpc\Data\PipelinePayload;
use OybekDaniyarov\LaravelTrpc\Data\RouteData;
use OybekDaniyarov\LaravelTrpc\Data\RouteTypeInfo;
use OybekDaniyarov\LaravelTrpc\TrpcConfig;

// Note: ExtractTypesPipe depends on RouteTypeExtractor which requires Laravel Surveyor
// with complex bindings. These tests focus on RouteTypeInfo and RouteData behavior.

it('RouteTypeInfo can be created with defaults', function () {
    $typeInfo = new RouteTypeInfo;

    expect($typeInfo->requestType)->toBeNull()
        ->and($typeInfo->queryType)->toBeNull()
        ->and($typeInfo->responseType)->toBeNull()
        ->and($typeInfo->isCollection)->toBeFalse()
        ->and($typeInfo->isPaginated)->toBeFalse();
});

it('RouteTypeInfo can be created with types', function () {
    $typeInfo = new RouteTypeInfo(
        requestType: 'App.Data.UserData',
        queryType: 'App.Data.FilterData',
        responseType: 'App.Data.ResponseData',
        isCollection: true,
        isPaginated: false,
    );

    expect($typeInfo->requestType)->toBe('App.Data.UserData')
        ->and($typeInfo->queryType)->toBe('App.Data.FilterData')
        ->and($typeInfo->responseType)->toBe('App.Data.ResponseData')
        ->and($typeInfo->isCollection)->toBeTrue();
});

it('RouteTypeInfo returns TypeScript request type', function () {
    $typeInfo = new RouteTypeInfo(
        requestType: 'App.Data.UserData',
    );

    expect($typeInfo->getTypeScriptRequestType())->toBe('App.Data.UserData');
});

it('RouteTypeInfo returns void for null request type', function () {
    $typeInfo = new RouteTypeInfo;

    expect($typeInfo->getTypeScriptRequestType())->toBe('void');
});

it('RouteData includes type information', function () {
    $route = new RouteData(
        method: 'post',
        path: 'api/users',
        name: 'users.store',
        group: 'users',
        requestType: 'App.Data.CreateUserData',
        responseType: 'App.Data.UserData',
        hasRequest: true,
        hasResponse: true,
    );

    expect($route->requestType)->toBe('App.Data.CreateUserData')
        ->and($route->responseType)->toBe('App.Data.UserData')
        ->and($route->hasRequest)->toBeTrue()
        ->and($route->hasResponse)->toBeTrue();
});

it('RouteData can be converted to array', function () {
    $route = new RouteData(
        method: 'get',
        path: 'api/users',
        name: 'users.index',
        group: 'users',
        pathParams: ['id'],
    );

    $array = $route->toArray();

    expect($array)->toHaveKey('method')
        ->and($array)->toHaveKey('path')
        ->and($array)->toHaveKey('name')
        ->and($array)->toHaveKey('group')
        ->and($array)->toHaveKey('pathParams')
        ->and($array['method'])->toBe('get')
        ->and($array['pathParams'])->toBe(['id']);
});

it('PipelinePayload can store route types in metadata', function () {
    $config = new TrpcConfig([]);
    $payload = PipelinePayload::create($config);

    $routeTypes = [
        'api/users' => new RouteTypeInfo(requestType: 'UserData'),
    ];

    $payload->withMetadata('routeTypes', $routeTypes);

    expect($payload->getMetadata('routeTypes'))->toBe($routeTypes);
});

it('RouteData supports pagination flags', function () {
    $route = new RouteData(
        method: 'get',
        path: 'api/users',
        name: 'users.index',
        group: 'users',
        isPaginated: true,
        isCollection: false,
    );

    expect($route->isPaginated)->toBeTrue()
        ->and($route->isCollection)->toBeFalse();
});

it('RouteData supports collection flags', function () {
    $route = new RouteData(
        method: 'get',
        path: 'api/users/all',
        name: 'users.all',
        group: 'users',
        isPaginated: false,
        isCollection: true,
    );

    expect($route->isCollection)->toBeTrue()
        ->and($route->isPaginated)->toBeFalse();
});
