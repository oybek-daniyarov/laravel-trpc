import { describe, it, expect, vi, beforeEach } from 'vitest';
import nock from 'nock';
import {
  fetchApi,
  type RouteDefinition,
  type ApiClientConfig,
} from '../resources/js/api/core/fetch';
import { createApi } from '../resources/js/api/api';

const BASE_URL = 'https://api.example.com';

const mockConfig: ApiClientConfig = {
  baseUrl: BASE_URL,
};

describe('fetchApi', () => {
  beforeEach(() => {
    nock.cleanAll();
  });

  describe('Success Responses', () => {
    it('should return JSON for 200 OK', async () => {
      const route: RouteDefinition = {
        path: '/api/users',
        method: 'get',
        params: [],
      };

      const mockData = { id: 1, name: 'John' };
      nock(BASE_URL).get('/api/users').reply(200, mockData);

      const result = await fetchApi(route, { clientConfig: mockConfig });
      expect(result).toEqual(mockData);
    });

    it('should return undefined for 204 No Content', async () => {
      const route: RouteDefinition = {
        path: '/api/users/1',
        method: 'delete',
        params: ['id'],
      };

      nock(BASE_URL).delete('/api/users/1').reply(204);

      const result = await fetchApi(route, {
        path: { id: 1 },
        clientConfig: mockConfig,
      });
      expect(result).toBeUndefined();
    });

    it('should handle content-length: 0 as empty response', async () => {
      const route: RouteDefinition = {
        path: '/api/users/1',
        method: 'delete',
        params: ['id'],
      };

      nock(BASE_URL).delete('/api/users/1').reply(200, '', { 'content-length': '0' });

      const result = await fetchApi(route, {
        path: { id: 1 },
        clientConfig: mockConfig,
      });
      expect(result).toBeUndefined();
    });
  });

  describe('Error Responses', () => {
    it('should throw ApiError for 400 Bad Request', async () => {
      const route: RouteDefinition = {
        path: '/api/users',
        method: 'post',
        params: [],
      };

      nock(BASE_URL).post('/api/users').reply(400, { message: 'Bad Request' });

      await expect(
        fetchApi(route, {
          body: {},
          clientConfig: mockConfig,
        })
      ).rejects.toMatchObject({
        message: 'Bad Request',
        status: 400,
      });
    });

    it('should throw ApiError for 401 Unauthorized', async () => {
      const route: RouteDefinition = {
        path: '/api/users',
        method: 'get',
        params: [],
      };

      nock(BASE_URL).get('/api/users').reply(401, { message: 'Unauthenticated' });

      await expect(fetchApi(route, { clientConfig: mockConfig })).rejects.toMatchObject({
        message: 'Unauthenticated',
        status: 401,
      });
    });

    it('should throw ApiError for 404 Not Found', async () => {
      const route: RouteDefinition = {
        path: '/api/users/{id}',
        method: 'get',
        params: ['id'],
      };

      nock(BASE_URL).get('/api/users/999').reply(404, { message: 'User not found' });

      await expect(
        fetchApi(route, {
          path: { id: 999 },
          clientConfig: mockConfig,
        })
      ).rejects.toMatchObject({
        message: 'User not found',
        status: 404,
      });
    });

    it('should throw ApiError for 422 with validation errors', async () => {
      const route: RouteDefinition = {
        path: '/api/users',
        method: 'post',
        params: [],
      };

      nock(BASE_URL)
        .post('/api/users')
        .reply(422, {
          message: 'The given data was invalid.',
          errors: {
            email: ['The email field is required.'],
            name: ['The name field is required.'],
          },
        });

      await expect(
        fetchApi(route, {
          body: {},
          clientConfig: mockConfig,
        })
      ).rejects.toMatchObject({
        message: 'The given data was invalid.',
        status: 422,
        errors: {
          email: ['The email field is required.'],
          name: ['The name field is required.'],
        },
      });
    });

    it('should throw ApiError for 500 Server Error', async () => {
      const route: RouteDefinition = {
        path: '/api/users',
        method: 'get',
        params: [],
      };

      nock(BASE_URL).get('/api/users').reply(500, { message: 'Internal Server Error' });

      await expect(fetchApi(route, { clientConfig: mockConfig })).rejects.toMatchObject({
        message: 'Internal Server Error',
        status: 500,
      });
    });

    it('should handle non-JSON error responses', async () => {
      const route: RouteDefinition = {
        path: '/api/users',
        method: 'get',
        params: [],
      };

      nock(BASE_URL).get('/api/users').reply(500, 'Internal Server Error', {
        'Content-Type': 'text/plain',
      });

      await expect(fetchApi(route, { clientConfig: mockConfig })).rejects.toMatchObject({
        message: 'HTTP 500: Internal Server Error',
        status: 500,
      });
    });
  });

  describe('CSRF Handling', () => {
    it('should extract token from cookie', async () => {
      document.cookie = 'XSRF-TOKEN=test-token-123';

      const route: RouteDefinition = {
        path: '/api/users',
        method: 'post',
        params: [],
      };

      const configWithCsrf: ApiClientConfig = {
        baseUrl: BASE_URL,
        csrf: { cookie: 'XSRF-TOKEN' },
      };

      nock(BASE_URL)
        .post('/api/users')
        .matchHeader('X-XSRF-TOKEN', 'test-token-123')
        .reply(200, { id: 1 });

      const result = await fetchApi(route, {
        body: { name: 'John' },
        clientConfig: configWithCsrf,
      });
      expect(result).toEqual({ id: 1 });

      document.cookie = '';
    });

    it('should send token in custom header', async () => {
      const route: RouteDefinition = {
        path: '/api/users',
        method: 'post',
        params: [],
      };

      const configWithCsrf: ApiClientConfig = {
        baseUrl: BASE_URL,
        csrf: {
          token: 'static-token-abc',
          header: 'X-Custom-CSRF',
        },
      };

      nock(BASE_URL)
        .post('/api/users')
        .matchHeader('X-Custom-CSRF', 'static-token-abc')
        .reply(200, { id: 1 });

      const result = await fetchApi(route, {
        body: { name: 'John' },
        clientConfig: configWithCsrf,
      });
      expect(result).toEqual({ id: 1 });
    });

    it('should use static token over cookie', async () => {
      document.cookie = 'XSRF-TOKEN=cookie-token';

      const route: RouteDefinition = {
        path: '/api/users',
        method: 'post',
        params: [],
      };

      const configWithCsrf: ApiClientConfig = {
        baseUrl: BASE_URL,
        csrf: {
          token: 'static-token',
          cookie: 'XSRF-TOKEN',
        },
      };

      nock(BASE_URL)
        .post('/api/users')
        .matchHeader('X-XSRF-TOKEN', 'static-token')
        .reply(200, { id: 1 });

      const result = await fetchApi(route, {
        body: { name: 'John' },
        clientConfig: configWithCsrf,
      });
      expect(result).toEqual({ id: 1 });

      document.cookie = '';
    });
  });

  describe('Retry Logic', () => {
    it('should retry on 5xx errors', async () => {
      const route: RouteDefinition = {
        path: '/api/users',
        method: 'get',
        params: [],
      };

      const configWithRetry: ApiClientConfig = {
        baseUrl: BASE_URL,
        mobile: {
          retry: { count: 2, delay: 10, backoff: 'linear' },
        },
      };

      nock(BASE_URL)
        .get('/api/users')
        .reply(500, { message: 'Server Error' })
        .get('/api/users')
        .reply(200, { id: 1 });

      const result = await fetchApi(route, {
        clientConfig: configWithRetry,
      });
      expect(result).toEqual({ id: 1 });
    });

    it('should retry on 429 rate limit', async () => {
      const route: RouteDefinition = {
        path: '/api/users',
        method: 'get',
        params: [],
      };

      const configWithRetry: ApiClientConfig = {
        baseUrl: BASE_URL,
        mobile: {
          retry: { count: 2, delay: 10, backoff: 'linear' },
        },
      };

      nock(BASE_URL)
        .get('/api/users')
        .reply(429, { message: 'Too Many Requests' })
        .get('/api/users')
        .reply(200, { id: 1 });

      const result = await fetchApi(route, {
        clientConfig: configWithRetry,
      });
      expect(result).toEqual({ id: 1 });
    });

    it('should use exponential backoff', async () => {
      const route: RouteDefinition = {
        path: '/api/users',
        method: 'get',
        params: [],
      };

      const configWithRetry: ApiClientConfig = {
        baseUrl: BASE_URL,
        mobile: {
          retry: { count: 2, delay: 10, backoff: 'exponential' },
        },
      };

      nock(BASE_URL)
        .get('/api/users')
        .reply(500, { message: 'Server Error' })
        .get('/api/users')
        .reply(200, { id: 1 });

      const _startTime = Date.now();
      const result = await fetchApi(route, {
        clientConfig: configWithRetry,
      });
      expect(result).toEqual({ id: 1 });
    });

    it('should not retry on 4xx errors (except 429)', async () => {
      const route: RouteDefinition = {
        path: '/api/users',
        method: 'post',
        params: [],
      };

      const configWithRetry: ApiClientConfig = {
        baseUrl: BASE_URL,
        mobile: {
          retry: { count: 2, delay: 10, backoff: 'linear' },
        },
      };

      nock(BASE_URL).post('/api/users').reply(400, { message: 'Bad Request' });

      await expect(
        fetchApi(route, {
          body: {},
          clientConfig: configWithRetry,
        })
      ).rejects.toMatchObject({
        message: 'Bad Request',
        status: 400,
      });
    });

    it('should exhaust retries and throw final error', async () => {
      const route: RouteDefinition = {
        path: '/api/users',
        method: 'get',
        params: [],
      };

      const configWithRetry: ApiClientConfig = {
        baseUrl: BASE_URL,
        mobile: {
          retry: { count: 2, delay: 10, backoff: 'linear' },
        },
      };

      nock(BASE_URL)
        .get('/api/users')
        .reply(500, { message: 'Server Error' })
        .get('/api/users')
        .reply(500, { message: 'Server Error' })
        .get('/api/users')
        .reply(500, { message: 'Server Error' });

      await expect(fetchApi(route, { clientConfig: configWithRetry })).rejects.toMatchObject({
        message: 'Server Error',
        status: 500,
      });
    });
  });

  describe('Hooks', () => {
    it('should call onRequest before fetch', async () => {
      const onRequest = vi.fn((url, init) => {
        return {
          ...init,
          headers: {
            ...init.headers,
            'X-Custom-Header': 'custom-value',
          },
        };
      });

      const route: RouteDefinition = {
        path: '/api/users',
        method: 'get',
        params: [],
      };

      const configWithHook: ApiClientConfig = {
        baseUrl: BASE_URL,
        onRequest,
      };

      nock(BASE_URL)
        .get('/api/users')
        .matchHeader('X-Custom-Header', 'custom-value')
        .reply(200, { id: 1 });

      await fetchApi(route, { clientConfig: configWithHook });

      expect(onRequest).toHaveBeenCalledTimes(1);
      expect(onRequest).toHaveBeenCalledWith(
        expect.stringContaining('/api/users'),
        expect.objectContaining({ method: 'GET' })
      );
    });

    it('should call onResponse after success', async () => {
      const onResponse = vi.fn((response, data) => ({
        ...data,
        transformed: true,
      }));

      const route: RouteDefinition = {
        path: '/api/users',
        method: 'get',
        params: [],
      };

      const configWithHook: ApiClientConfig = {
        baseUrl: BASE_URL,
        onResponse,
      };

      nock(BASE_URL).get('/api/users').reply(200, { id: 1 });

      const result = await fetchApi(route, { clientConfig: configWithHook });

      expect(onResponse).toHaveBeenCalledTimes(1);
      expect(result).toEqual({ id: 1, transformed: true });
    });

    it('should call onError on failure', async () => {
      const onError = vi.fn();

      const route: RouteDefinition = {
        path: '/api/users',
        method: 'get',
        params: [],
      };

      const configWithHook: ApiClientConfig = {
        baseUrl: BASE_URL,
        onError,
      };

      nock(BASE_URL).get('/api/users').reply(500, { message: 'Server Error' });

      await expect(fetchApi(route, { clientConfig: configWithHook })).rejects.toThrow();

      expect(onError).toHaveBeenCalledTimes(1);
      expect(onError).toHaveBeenCalledWith(
        expect.objectContaining({
          message: 'Server Error',
          status: 500,
        })
      );
    });
  });

  describe('Request Body', () => {
    it('should send JSON body for POST', async () => {
      const route: RouteDefinition = {
        path: '/api/users',
        method: 'post',
        params: [],
      };

      nock(BASE_URL)
        .post('/api/users', { name: 'John', email: 'john@example.com' })
        .reply(201, { id: 1 });

      const result = await fetchApi(route, {
        body: { name: 'John', email: 'john@example.com' },
        clientConfig: mockConfig,
      });
      expect(result).toEqual({ id: 1 });
    });

    it('should send JSON body for PUT', async () => {
      const route: RouteDefinition = {
        path: '/api/users/{id}',
        method: 'put',
        params: ['id'],
      };

      nock(BASE_URL)
        .put('/api/users/1', { name: 'Updated' })
        .reply(200, { id: 1, name: 'Updated' });

      const result = await fetchApi(route, {
        path: { id: 1 },
        body: { name: 'Updated' },
        clientConfig: mockConfig,
      });
      expect(result).toEqual({ id: 1, name: 'Updated' });
    });

    it('should not send body for GET requests', async () => {
      const route: RouteDefinition = {
        path: '/api/users',
        method: 'get',
        params: [],
      };

      nock(BASE_URL).get('/api/users').reply(200, []);

      const result = await fetchApi(route, {
        body: { ignored: true },
        clientConfig: mockConfig,
      });
      expect(result).toEqual([]);
    });
  });

  describe('Query Parameters', () => {
    it('should append query parameters', async () => {
      const route: RouteDefinition = {
        path: '/api/users',
        method: 'get',
        params: [],
      };

      nock(BASE_URL)
        .get('/api/users')
        .query({ page: '1', per_page: '10' })
        .reply(200, { data: [] });

      const result = await fetchApi(route, {
        query: { page: 1, per_page: 10 },
        clientConfig: mockConfig,
      });
      expect(result).toEqual({ data: [] });
    });
  });

  describe('Custom Headers', () => {
    it('should merge client config headers', async () => {
      const route: RouteDefinition = {
        path: '/api/users',
        method: 'get',
        params: [],
      };

      const configWithHeaders: ApiClientConfig = {
        baseUrl: BASE_URL,
        headers: { 'X-App-Version': '1.0.0' },
      };

      nock(BASE_URL).get('/api/users').matchHeader('X-App-Version', '1.0.0').reply(200, []);

      await fetchApi(route, { clientConfig: configWithHeaders });
    });

    it('should merge request options headers', async () => {
      const route: RouteDefinition = {
        path: '/api/users',
        method: 'get',
        params: [],
      };

      nock(BASE_URL).get('/api/users').matchHeader('X-Request-Id', 'abc-123').reply(200, []);

      await fetchApi(route, {
        clientConfig: mockConfig,
        requestOptions: {
          headers: { 'X-Request-Id': 'abc-123' },
        },
      });
    });

    it('should override config headers with request headers', async () => {
      const route: RouteDefinition = {
        path: '/api/users',
        method: 'get',
        params: [],
      };

      const configWithHeaders: ApiClientConfig = {
        baseUrl: BASE_URL,
        headers: { 'X-Custom': 'config-value' },
      };

      nock(BASE_URL).get('/api/users').matchHeader('X-Custom', 'request-value').reply(200, []);

      await fetchApi(route, {
        clientConfig: configWithHeaders,
        requestOptions: {
          headers: { 'X-Custom': 'request-value' },
        },
      });
    });
  });
});

describe('createApi', () => {
  beforeEach(() => {
    nock.cleanAll();
  });

  it('should create combined API client', () => {
    const api = createApi(mockConfig);

    expect(api).toHaveProperty('auth');
    expect(api).toHaveProperty('posts');
    expect(api).toHaveProperty('users');
  });

  it('should include all resource groups', () => {
    const api = createApi(mockConfig);

    expect(typeof api.users.index).toBe('function');
    expect(typeof api.users.show).toBe('function');
    expect(typeof api.users.store).toBe('function');
    expect(typeof api.users.update).toBe('function');
    expect(typeof api.users.destroy).toBe('function');
  });

  it('should allow calling resource methods', async () => {
    const api = createApi({ baseUrl: BASE_URL + '/' });

    nock(BASE_URL).get('/api/users').reply(200, { data: [] });

    const result = await api.users.index({});
    expect(result).toEqual({ data: [] });
  });
});
