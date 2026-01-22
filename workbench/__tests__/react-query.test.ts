import { describe, it, expect } from 'vitest';
import {
  queryKey,
  baseQueryKey,
  mutationKey,
  createQueryKeys,
  createQueryOptions,
  createInfiniteQueryOptions,
} from '../resources/js/api/react-query';
import type { ApiClientConfig } from '../resources/js/api/core/fetch';

const mockConfig: ApiClientConfig = {
  baseUrl: 'https://api.example.com/',
};

describe('queryKey', () => {
  it('should return [routeName] without params', () => {
    const result = queryKey('users.index');
    expect(result).toEqual(['users.index']);
  });

  it('should return [routeName, params] with path params', () => {
    const result = queryKey('users.show', { path: { user: 123 } });
    expect(result).toEqual(['users.show', { path: { user: 123 } }]);
  });

  it('should return [routeName, params] with query params', () => {
    const result = queryKey('users.index', { query: { page: 1 } });
    expect(result).toEqual(['users.index', { query: { page: 1 } }]);
  });

  it('should return [routeName, params] with both path and query params', () => {
    const result = queryKey('users.show', { path: { user: 123 }, query: { include: 'posts' } });
    expect(result).toEqual(['users.show', { path: { user: 123 }, query: { include: 'posts' } }]);
  });

  it('should return [routeName] when params object is empty', () => {
    const result = queryKey('users.index', {});
    expect(result).toEqual(['users.index']);
  });

  it('should return [routeName] when params is undefined', () => {
    const result = queryKey('users.index', undefined);
    expect(result).toEqual(['users.index']);
  });
});

describe('baseQueryKey', () => {
  it('should return [routeName] without params', () => {
    const result = baseQueryKey('users.index');
    expect(result).toEqual(['users.index']);
  });

  it('should always return single element array', () => {
    const result = baseQueryKey('users.show');
    expect(result).toHaveLength(1);
    expect(result[0]).toBe('users.show');
  });
});

describe('mutationKey', () => {
  it('should return [routeName]', () => {
    const result = mutationKey('users.store');
    expect(result).toEqual(['users.store']);
  });

  it('should always return single element array', () => {
    const result = mutationKey('users.update');
    expect(result).toHaveLength(1);
    expect(result[0]).toBe('users.update');
  });
});

describe('createQueryKeys', () => {
  it('should create query keys object with all method', () => {
    const userKeys = createQueryKeys('users');
    expect(userKeys.all).toEqual(['users']);
  });

  it('should create lists() method', () => {
    const userKeys = createQueryKeys('users');
    expect(userKeys.lists()).toEqual(['users', 'list']);
  });

  it('should create list() method with filters', () => {
    const userKeys = createQueryKeys('users');
    expect(userKeys.list({ page: 1, search: 'john' })).toEqual([
      'users',
      'list',
      { page: 1, search: 'john' },
    ]);
  });

  it('should create details() method', () => {
    const userKeys = createQueryKeys('users');
    expect(userKeys.details()).toEqual(['users', 'detail']);
  });

  it('should create detail() method with id', () => {
    const userKeys = createQueryKeys('users');
    expect(userKeys.detail(123)).toEqual(['users', 'detail', 123]);
  });

  it('should handle string IDs', () => {
    const userKeys = createQueryKeys('users');
    expect(userKeys.detail('abc-123')).toEqual(['users', 'detail', 'abc-123']);
  });
});

describe('createQueryOptions', () => {
  it('should return valid query options object', () => {
    const options = createQueryOptions('users.index', {}, mockConfig);

    expect(options).toHaveProperty('queryKey');
    expect(options).toHaveProperty('queryFn');
    expect(options).toHaveProperty('enabled');
  });

  it('should include queryKey and queryFn', () => {
    const options = createQueryOptions('users.index', {}, mockConfig);

    expect(options.queryKey).toEqual(['users.index']);
    expect(typeof options.queryFn).toBe('function');
  });

  it('should pass enabled option', () => {
    const options = createQueryOptions('users.index', { enabled: false }, mockConfig);
    expect(options.enabled).toBe(false);
  });

  it('should default enabled to true', () => {
    const options = createQueryOptions('users.index', {}, mockConfig);
    expect(options.enabled).toBe(true);
  });

  it('should include path params in queryKey', () => {
    const options = createQueryOptions('users.show', { path: { user: 123 } }, mockConfig);
    expect(options.queryKey).toEqual(['users.show', { path: { user: 123 } }]);
  });

  it('should include query params in queryKey', () => {
    const options = createQueryOptions('users.index', { query: { page: 1 } }, mockConfig);
    expect(options.queryKey).toEqual(['users.index', { query: { page: 1 } }]);
  });

  it('should pass staleTime option', () => {
    const options = createQueryOptions('users.index', { staleTime: 5000 }, mockConfig);
    expect(options.staleTime).toBe(5000);
  });

  it('should pass gcTime option', () => {
    const options = createQueryOptions('users.index', { gcTime: 10000 }, mockConfig);
    expect(options.gcTime).toBe(10000);
  });

  it('should pass refetchOnWindowFocus option', () => {
    const options = createQueryOptions('users.index', { refetchOnWindowFocus: false }, mockConfig);
    expect(options.refetchOnWindowFocus).toBe(false);
  });

  it('should pass retry option', () => {
    const options = createQueryOptions('users.index', { retry: 3 }, mockConfig);
    expect(options.retry).toBe(3);
  });

  it('should pass select option', () => {
    const selectFn = (data: unknown) => data;
    const options = createQueryOptions('users.index', { select: selectFn }, mockConfig);
    expect(options.select).toBe(selectFn);
  });

  it('should pass placeholderData option', () => {
    const placeholderData = {
      data: [],
      meta: { current_page: 1, last_page: 1, per_page: 15, total: 0, from: null, to: null },
      links: { first: '', last: '', prev: null, next: null },
    };
    const options = createQueryOptions('users.index', { placeholderData }, mockConfig);
    expect(options.placeholderData).toEqual(placeholderData);
  });
});

describe('createInfiniteQueryOptions', () => {
  it('should return valid infinite query options', () => {
    const options = createInfiniteQueryOptions('users.index', {}, mockConfig);

    expect(options).toHaveProperty('queryKey');
    expect(options).toHaveProperty('queryFn');
    expect(options).toHaveProperty('initialPageParam');
  });

  it('should include initialPageParam defaulting to 1', () => {
    const options = createInfiniteQueryOptions('users.index', {}, mockConfig);
    expect(options.initialPageParam).toBe(1);
  });

  it('should pass custom initialPageParam', () => {
    const options = createInfiniteQueryOptions('users.index', { initialPageParam: 0 }, mockConfig);
    expect(options.initialPageParam).toBe(0);
  });

  it('should pass getNextPageParam', () => {
    const getNextPageParam = () => 2;
    const options = createInfiniteQueryOptions('users.index', { getNextPageParam }, mockConfig);
    expect(options.getNextPageParam).toBe(getNextPageParam);
  });

  it('should pass getPreviousPageParam', () => {
    const getPreviousPageParam = () => 1;
    const options = createInfiniteQueryOptions('users.index', { getPreviousPageParam }, mockConfig);
    expect(options.getPreviousPageParam).toBe(getPreviousPageParam);
  });

  it('should include query params in queryKey', () => {
    const options = createInfiniteQueryOptions(
      'users.index',
      { query: { per_page: 20 } },
      mockConfig
    );
    expect(options.queryKey).toEqual(['users.index', { query: { per_page: 20 } }]);
  });
});
