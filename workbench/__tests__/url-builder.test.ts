import { describe, it, expect } from 'vitest';
import { url, getRoute, requiresPath, getMethod, getPath } from '../resources/js/api/url-builder';

describe('url', () => {
  it('should generate URL from route name', () => {
    const result = url('users.index');
    expect(result).toBe('api/users');
  });

  it('should substitute path parameters', () => {
    const result = url('users.show', { user: 123 });
    expect(result).toBe('api/users/123');
  });

  it('should append query parameters', () => {
    const result = url('users.index', null, { query: { page: 1 } });
    expect(result).toBe('api/users?page=1');
  });

  it('should handle both path and query parameters', () => {
    const result = url('users.show', { user: 123 }, { query: { include: 'posts' } });
    expect(result).toBe('api/users/123?include=posts');
  });

  it('should encode path parameter values', () => {
    const result = url('users.show', { user: 'john doe' });
    expect(result).toBe('api/users/john%20doe');
  });

  it('should handle array query parameters', () => {
    const result = url('users.index', null, { query: { ids: [1, 2, 3] } });
    expect(result).toBe('api/users?ids%5B%5D=1&ids%5B%5D=2&ids%5B%5D=3');
  });

  it('should skip null and undefined query values', () => {
    const result = url('users.index', null, { query: { page: 1, filter: null, sort: undefined } });
    expect(result).toBe('api/users?page=1');
  });

  it('should handle multiple query parameters', () => {
    const result = url('users.index', null, { query: { page: 1, per_page: 10, sort: 'name' } });
    expect(result).toBe('api/users?page=1&per_page=10&sort=name');
  });
});

describe('getRoute', () => {
  it('should return route metadata for users.index', () => {
    const route = getRoute('users.index');
    expect(route.path).toBe('api/users');
    expect(route.method).toBe('get');
    expect(route.params).toEqual([]);
  });

  it('should return route metadata for users.show', () => {
    const route = getRoute('users.show');
    expect(route.path).toBe('api/users/{user}');
    expect(route.method).toBe('get');
    expect(route.params).toEqual(['user']);
  });

  it('should return route metadata for users.store', () => {
    const route = getRoute('users.store');
    expect(route.path).toBe('api/users');
    expect(route.method).toBe('post');
    expect(route.params).toEqual([]);
  });

  it('should return route metadata for users.update', () => {
    const route = getRoute('users.update');
    expect(route.path).toBe('api/users/{user}');
    expect(route.method).toBe('put');
    expect(route.params).toEqual(['user']);
  });

  it('should return route metadata for users.destroy', () => {
    const route = getRoute('users.destroy');
    expect(route.path).toBe('api/users/{user}');
    expect(route.method).toBe('delete');
    expect(route.params).toEqual(['user']);
  });
});

describe('requiresPath', () => {
  it('should return true for routes with params', () => {
    expect(requiresPath('users.show')).toBe(true);
    expect(requiresPath('users.update')).toBe(true);
    expect(requiresPath('users.destroy')).toBe(true);
  });

  it('should return false for routes without params', () => {
    expect(requiresPath('users.index')).toBe(false);
    expect(requiresPath('users.store')).toBe(false);
  });
});

describe('getMethod', () => {
  it('should return HTTP method for GET route', () => {
    expect(getMethod('users.index')).toBe('get');
    expect(getMethod('users.show')).toBe('get');
  });

  it('should return HTTP method for POST route', () => {
    expect(getMethod('users.store')).toBe('post');
  });

  it('should return HTTP method for PUT route', () => {
    expect(getMethod('users.update')).toBe('put');
  });

  it('should return HTTP method for DELETE route', () => {
    expect(getMethod('users.destroy')).toBe('delete');
  });
});

describe('getPath', () => {
  it('should return path template for route', () => {
    expect(getPath('users.index')).toBe('api/users');
    expect(getPath('users.show')).toBe('api/users/{user}');
    expect(getPath('users.store')).toBe('api/users');
    expect(getPath('users.update')).toBe('api/users/{user}');
    expect(getPath('users.destroy')).toBe('api/users/{user}');
  });
});
