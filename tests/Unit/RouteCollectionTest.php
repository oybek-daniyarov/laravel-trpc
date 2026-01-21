<?php

declare(strict_types=1);

use OybekDaniyarov\LaravelTrpc\Collections\RouteCollection;
use OybekDaniyarov\LaravelTrpc\Data\RouteData;

it('can add routes to collection', function () {
    $collection = new RouteCollection;

    $route = new RouteData(
        method: 'get',
        path: 'api/users',
        name: 'users.index',
        group: 'users',
    );

    $collection->add($route);

    expect($collection->count())->toBe(1)
        ->and($collection->has('users.index'))->toBeTrue();
});

it('can get route by name', function () {
    $collection = new RouteCollection;

    $route = new RouteData(
        method: 'get',
        path: 'api/users/{id}',
        name: 'users.show',
        group: 'users',
        pathParams: ['id'],
    );

    $collection->add($route);

    $found = $collection->get('users.show');

    expect($found)->not->toBeNull()
        ->and($found->name)->toBe('users.show')
        ->and($found->pathParams)->toBe(['id']);
});

it('returns null for non-existent route', function () {
    $collection = new RouteCollection;

    expect($collection->get('non.existent'))->toBeNull()
        ->and($collection->has('non.existent'))->toBeFalse();
});

it('can filter routes', function () {
    $collection = new RouteCollection([
        new RouteData(method: 'get', path: 'api/users', name: 'users.index', group: 'users'),
        new RouteData(method: 'post', path: 'api/users', name: 'users.store', group: 'users'),
        new RouteData(method: 'get', path: 'api/posts', name: 'posts.index', group: 'posts'),
    ]);

    $filtered = $collection->filter(fn (RouteData $r) => $r->group === 'users');

    expect($filtered->count())->toBe(2);
});

it('can group routes by key', function () {
    $collection = new RouteCollection([
        new RouteData(method: 'get', path: 'api/users', name: 'users.index', group: 'users'),
        new RouteData(method: 'post', path: 'api/users', name: 'users.store', group: 'users'),
        new RouteData(method: 'get', path: 'api/posts', name: 'posts.index', group: 'posts'),
    ]);

    $grouped = $collection->groupBy(fn (RouteData $r) => $r->group);

    expect($grouped)->toHaveKeys(['users', 'posts'])
        ->and($grouped['users']->count())->toBe(2)
        ->and($grouped['posts']->count())->toBe(1);
});

it('can sort routes by name', function () {
    $collection = new RouteCollection([
        new RouteData(method: 'get', path: 'api/posts', name: 'posts.index', group: 'posts'),
        new RouteData(method: 'get', path: 'api/users', name: 'users.index', group: 'users'),
        new RouteData(method: 'get', path: 'api/auth', name: 'auth.login', group: 'auth'),
    ]);

    $sorted = $collection->sortByName();
    $names = $sorted->map(fn (RouteData $r) => $r->name);

    expect($names)->toBe(['auth.login', 'posts.index', 'users.index']);
});

it('can merge collections', function () {
    $collection1 = new RouteCollection([
        new RouteData(method: 'get', path: 'api/users', name: 'users.index', group: 'users'),
    ]);

    $collection2 = new RouteCollection([
        new RouteData(method: 'get', path: 'api/posts', name: 'posts.index', group: 'posts'),
    ]);

    $merged = $collection1->merge($collection2);

    expect($merged->count())->toBe(2)
        ->and($merged->has('users.index'))->toBeTrue()
        ->and($merged->has('posts.index'))->toBeTrue();
});

it('skips duplicates when merging', function () {
    $collection1 = new RouteCollection([
        new RouteData(method: 'get', path: 'api/users', name: 'users.index', group: 'users'),
    ]);

    $collection2 = new RouteCollection([
        new RouteData(method: 'get', path: 'api/users', name: 'users.index', group: 'users'),
    ]);

    $merged = $collection1->merge($collection2);

    expect($merged->count())->toBe(1);
});
