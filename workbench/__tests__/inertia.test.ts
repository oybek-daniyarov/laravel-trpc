import { describe, it, expect } from 'vitest';
import { route, formAction, linkProps, visit, typedFormData } from '../resources/js/api/inertia';

describe('route', () => {
  it('should generate URL like Ziggy', () => {
    const result = route('users.index');
    expect(result).toBe('api/users');
  });

  it('should handle path params', () => {
    const result = route('users.show', { user: 123 });
    expect(result).toBe('api/users/123');
  });

  it('should handle query params', () => {
    const result = route('users.index', null, { page: 1 });
    expect(result).toBe('api/users?page=1');
  });

  it('should handle both path and query params', () => {
    const result = route('users.show', { user: 123 }, { include: 'posts' });
    expect(result).toBe('api/users/123?include=posts');
  });

  it('should encode path parameters', () => {
    const result = route('users.show', { user: 'john doe' });
    expect(result).toBe('api/users/john%20doe');
  });

  it('should handle array query parameters', () => {
    const result = route('users.index', null, { ids: [1, 2, 3] });
    expect(result).toBe('api/users?ids%5B%5D=1&ids%5B%5D=2&ids%5B%5D=3');
  });
});

describe('formAction', () => {
  it('should return { action, method: "get" } for GET routes', () => {
    const result = formAction('users.index');
    expect(result).toEqual({
      action: 'api/users',
      method: 'get',
    });
  });

  it('should return { action, method: "post" } for POST routes', () => {
    const result = formAction('users.store');
    expect(result).toEqual({
      action: 'api/users',
      method: 'post',
    });
  });

  it('should return { action, method: "post", _method: "PUT" } for PUT routes', () => {
    const result = formAction('users.update', { user: 1 });
    expect(result).toEqual({
      action: 'api/users/1',
      method: 'post',
      _method: 'PUT',
    });
  });

  it('should return { action, method: "post", _method: "DELETE" } for DELETE routes', () => {
    const result = formAction('users.destroy', { user: 1 });
    expect(result).toEqual({
      action: 'api/users/1',
      method: 'post',
      _method: 'DELETE',
    });
  });

  it('should handle show route as GET', () => {
    const result = formAction('users.show', { user: 123 });
    expect(result).toEqual({
      action: 'api/users/123',
      method: 'get',
    });
  });
});

describe('linkProps', () => {
  it('should return href', () => {
    const result = linkProps('users.index');
    expect(result.href).toBe('api/users');
  });

  it('should return href with path params', () => {
    const result = linkProps('users.show', { user: 123 });
    expect(result.href).toBe('api/users/123');
  });

  it('should return method for non-GET routes', () => {
    const result = linkProps('users.store');
    expect(result.method).toBe('post');
  });

  it('should not include method for GET routes', () => {
    const result = linkProps('users.index');
    expect(result.method).toBeUndefined();
  });

  it('should include preserveState option', () => {
    const result = linkProps('users.index', null, { preserveState: true });
    expect(result.preserveState).toBe(true);
  });

  it('should include preserveScroll option', () => {
    const result = linkProps('users.index', null, { preserveScroll: true });
    expect(result.preserveScroll).toBe(true);
  });

  it('should include both preserveState and preserveScroll options', () => {
    const result = linkProps('users.index', null, {
      preserveState: true,
      preserveScroll: false,
    });
    expect(result.preserveState).toBe(true);
    expect(result.preserveScroll).toBe(false);
  });

  it('should allow overriding method', () => {
    const result = linkProps('users.index', null, { method: 'post' });
    expect(result.method).toBe('post');
  });

  it('should return method for PUT routes', () => {
    const result = linkProps('users.update', { user: 1 });
    expect(result.method).toBe('put');
  });

  it('should return method for DELETE routes', () => {
    const result = linkProps('users.destroy', { user: 1 });
    expect(result.method).toBe('delete');
  });
});

describe('visit', () => {
  it('should return url and options', () => {
    const result = visit('users.index', null);
    expect(result.url).toBe('api/users');
    expect(result.options).toEqual({});
  });

  it('should handle path params', () => {
    const result = visit('users.show', { user: 123 });
    expect(result.url).toBe('api/users/123');
  });

  it('should handle query params', () => {
    const result = visit('users.index', null, { query: { page: 1 } });
    expect(result.url).toBe('api/users?page=1');
  });

  it('should pass visit options', () => {
    const result = visit('users.index', null, {
      preserveState: true,
      preserveScroll: true,
    });
    expect(result.options.preserveState).toBe(true);
    expect(result.options.preserveScroll).toBe(true);
  });

  it('should handle data option', () => {
    const result = visit('users.store', null, {
      data: { name: 'John', email: 'john@example.com', password: 'secret123' },
    });
    expect(result.options.data).toEqual({
      name: 'John',
      email: 'john@example.com',
      password: 'secret123',
    });
  });

  it('should not include query in options', () => {
    const result = visit('users.index', null, { query: { page: 1 } });
    expect(result.options).not.toHaveProperty('query');
  });
});

describe('typedFormData', () => {
  it('should return the initial data unchanged', () => {
    const data = { name: 'John', email: 'john@example.com', password: 'secret123' };
    const result = typedFormData('users.store', data);
    expect(result).toEqual(data);
  });

  it('should preserve all properties', () => {
    const data = {
      name: 'John',
      email: 'john@example.com',
      password: 'secret123',
    };
    const result = typedFormData('users.store', data);
    expect(result).toEqual(data);
  });
});
