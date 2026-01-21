<?php

declare(strict_types=1);

use OybekDaniyarov\LaravelTrpc\Data\RouteTypeInfo;

it('returns void for null request type', function () {
    $info = new RouteTypeInfo(requestType: null);

    expect($info->getTypeScriptRequestType())->toBe('void');
});

it('returns request type when set', function () {
    $info = new RouteTypeInfo(requestType: 'App.Data.UserPayload');

    expect($info->getTypeScriptRequestType())->toBe('App.Data.UserPayload');
});

it('returns unknown for null response type', function () {
    $info = new RouteTypeInfo(responseType: null);

    expect($info->getTypeScriptResponseType())->toBe('unknown');
});

it('returns response type when set', function () {
    $info = new RouteTypeInfo(responseType: 'App.Data.UserResponse');

    expect($info->getTypeScriptResponseType())->toBe('App.Data.UserResponse');
});

it('wraps response in Array when isCollection', function () {
    $info = new RouteTypeInfo(
        responseType: 'App.Data.UserResponse',
        isCollection: true,
    );

    expect($info->getTypeScriptResponseType())->toBe('Array<App.Data.UserResponse>');
});

it('wraps response in PaginatedResponse when isPaginated', function () {
    $info = new RouteTypeInfo(
        responseType: 'App.Data.UserResponse',
        isPaginated: true,
    );

    expect($info->getTypeScriptResponseType())->toBe('PaginatedResponse<App.Data.UserResponse>');
});

it('prefers isPaginated over isCollection', function () {
    $info = new RouteTypeInfo(
        responseType: 'App.Data.UserResponse',
        isCollection: true,
        isPaginated: true,
    );

    expect($info->getTypeScriptResponseType())->toBe('PaginatedResponse<App.Data.UserResponse>');
});

it('returns null for query type when not set', function () {
    $info = new RouteTypeInfo(queryType: null);

    expect($info->getTypeScriptQueryType())->toBeNull();
});

it('returns query type when set', function () {
    $info = new RouteTypeInfo(queryType: 'App.Data.FilterPayload');

    expect($info->getTypeScriptQueryType())->toBe('App.Data.FilterPayload');
});
