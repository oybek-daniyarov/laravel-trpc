import { describe, it, expect } from 'vitest';
import { buildUrl } from '../../resources/js/api/core/fetch';
import type { RouteDefinition } from '../../resources/js/api/core/fetch';

describe('buildUrl', () => {
  describe('Path Parameters', () => {
    it('should replace single path parameter', () => {
      const route: RouteDefinition = {
        path: 'api/users/{user}',
        method: 'get',
        params: ['user'],
      };

      const result = buildUrl(route, { user: 123 });
      expect(result).toBe('api/users/123');
    });

    it('should replace multiple path parameters', () => {
      const route: RouteDefinition = {
        path: 'api/posts/{post}/comments/{comment}',
        method: 'get',
        params: ['post', 'comment'],
      };

      const result = buildUrl(route, { post: 1, comment: 42 });
      expect(result).toBe('api/posts/1/comments/42');
    });

    it('should encode path parameter values', () => {
      const route: RouteDefinition = {
        path: 'api/users/{slug}',
        method: 'get',
        params: ['slug'],
      };

      const result = buildUrl(route, { slug: 'hello world' });
      expect(result).toBe('api/users/hello%20world');
    });

    it('should handle string path parameter values', () => {
      const route: RouteDefinition = {
        path: 'api/users/{user}',
        method: 'get',
        params: ['user'],
      };

      const result = buildUrl(route, { user: 'john-doe' });
      expect(result).toBe('api/users/john-doe');
    });
  });

  describe('Query Parameters', () => {
    it('should append query string', () => {
      const route: RouteDefinition = {
        path: 'api/users',
        method: 'get',
        params: [],
      };

      const result = buildUrl(route, null, { page: 1 });
      expect(result).toBe('api/users?page=1');
    });

    it('should handle arrays with [] suffix', () => {
      const route: RouteDefinition = {
        path: 'api/users',
        method: 'get',
        params: [],
      };

      const result = buildUrl(route, null, { ids: [1, 2, 3] });
      expect(result).toBe('api/users?ids%5B%5D=1&ids%5B%5D=2&ids%5B%5D=3');
    });

    it('should skip null values', () => {
      const route: RouteDefinition = {
        path: 'api/users',
        method: 'get',
        params: [],
      };

      const result = buildUrl(route, null, { page: 1, filter: null });
      expect(result).toBe('api/users?page=1');
    });

    it('should skip undefined values', () => {
      const route: RouteDefinition = {
        path: 'api/users',
        method: 'get',
        params: [],
      };

      const result = buildUrl(route, null, { page: 1, filter: undefined });
      expect(result).toBe('api/users?page=1');
    });

    it('should encode special characters', () => {
      const route: RouteDefinition = {
        path: 'api/search',
        method: 'get',
        params: [],
      };

      const result = buildUrl(route, null, { q: 'hello world' });
      expect(result).toBe('api/search?q=hello+world');
    });

    it('should handle boolean values', () => {
      const route: RouteDefinition = {
        path: 'api/users',
        method: 'get',
        params: [],
      };

      const result = buildUrl(route, null, { active: true, deleted: false });
      expect(result).toBe('api/users?active=true&deleted=false');
    });

    it('should handle multiple query parameters', () => {
      const route: RouteDefinition = {
        path: 'api/users',
        method: 'get',
        params: [],
      };

      const result = buildUrl(route, null, { page: 1, per_page: 10, sort: 'name' });
      expect(result).toBe('api/users?page=1&per_page=10&sort=name');
    });
  });

  describe('Edge Cases', () => {
    it('should handle empty path params', () => {
      const route: RouteDefinition = {
        path: 'api/users',
        method: 'get',
        params: [],
      };

      const result = buildUrl(route, {});
      expect(result).toBe('api/users');
    });

    it('should handle null path params', () => {
      const route: RouteDefinition = {
        path: 'api/users',
        method: 'get',
        params: [],
      };

      const result = buildUrl(route, null);
      expect(result).toBe('api/users');
    });

    it('should handle empty query params', () => {
      const route: RouteDefinition = {
        path: 'api/users',
        method: 'get',
        params: [],
      };

      const result = buildUrl(route, null, {});
      expect(result).toBe('api/users');
    });

    it('should handle both path and query params', () => {
      const route: RouteDefinition = {
        path: 'api/users/{user}',
        method: 'get',
        params: ['user'],
      };

      const result = buildUrl(route, { user: 123 }, { include: 'posts' });
      expect(result).toBe('api/users/123?include=posts');
    });

    it('should handle query with only null/undefined values', () => {
      const route: RouteDefinition = {
        path: 'api/users',
        method: 'get',
        params: [],
      };

      const result = buildUrl(route, null, { filter: null, sort: undefined });
      expect(result).toBe('api/users');
    });

    it('should handle empty arrays in query', () => {
      const route: RouteDefinition = {
        path: 'api/users',
        method: 'get',
        params: [],
      };

      const result = buildUrl(route, null, { ids: [] });
      expect(result).toBe('api/users');
    });

    it('should handle numeric query values', () => {
      const route: RouteDefinition = {
        path: 'api/users',
        method: 'get',
        params: [],
      };

      const result = buildUrl(route, null, { page: 1, limit: 25 });
      expect(result).toBe('api/users?page=1&limit=25');
    });
  });
});
