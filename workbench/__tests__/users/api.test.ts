import { describe, it, expect, beforeEach } from 'vitest';
import nock from 'nock';
import { createUsersApi } from '../../resources/js/api/users/api';
import type { ApiClientConfig } from '../../resources/js/api/core/fetch';

const BASE_URL = 'https://api.example.com';

const mockConfig: ApiClientConfig = {
  baseUrl: BASE_URL + '/',
};

describe('createUsersApi', () => {
  let usersApi: ReturnType<typeof createUsersApi>;

  beforeEach(() => {
    nock.cleanAll();
    usersApi = createUsersApi(mockConfig);
  });

  describe('index', () => {
    it('should GET /api/users', async () => {
      const mockResponse = {
        data: [
          { id: 1, name: 'John', email: 'john@example.com' },
          { id: 2, name: 'Jane', email: 'jane@example.com' },
        ],
        meta: { current_page: 1, last_page: 1, per_page: 15, total: 2 },
        links: { first: '/api/users?page=1', last: '/api/users?page=1', prev: null, next: null },
      };

      nock(BASE_URL).get('/api/users').reply(200, mockResponse);

      const result = await usersApi.index({});
      expect(result).toEqual(mockResponse);
    });

    it('should pass query params', async () => {
      const mockResponse = {
        data: [{ id: 1, name: 'John', email: 'john@example.com' }],
        meta: { current_page: 2, last_page: 5, per_page: 10, total: 50 },
        links: {
          first: '/api/users?page=1',
          last: '/api/users?page=5',
          prev: '/api/users?page=1',
          next: '/api/users?page=3',
        },
      };

      nock(BASE_URL)
        .get('/api/users')
        .query({ page: '2', per_page: '10' })
        .reply(200, mockResponse);

      const result = await usersApi.index({
        query: {
          page: 2,
          per_page: 10,
          search: null,
          sort_by: null,
          sort_dir: null,
          role: null,
          active: null,
        },
      });
      expect(result).toEqual(mockResponse);
    });

    it('should pass search query param', async () => {
      const mockResponse = {
        data: [{ id: 1, name: 'John', email: 'john@example.com' }],
        meta: { current_page: 1, last_page: 1, per_page: 15, total: 1 },
        links: { first: '/api/users?page=1', last: '/api/users?page=1', prev: null, next: null },
      };

      nock(BASE_URL).get('/api/users').query({ search: 'john' }).reply(200, mockResponse);

      const result = await usersApi.index({
        query: {
          search: 'john',
          page: null,
          per_page: null,
          sort_by: null,
          sort_dir: null,
          role: null,
          active: null,
        },
      });
      expect(result).toEqual(mockResponse);
    });

    it('should handle empty results', async () => {
      const mockResponse = {
        data: [],
        meta: { current_page: 1, last_page: 1, per_page: 15, total: 0 },
        links: { first: '/api/users?page=1', last: '/api/users?page=1', prev: null, next: null },
      };

      nock(BASE_URL).get('/api/users').reply(200, mockResponse);

      const result = await usersApi.index({});
      expect(result.data).toEqual([]);
      expect(result.meta.total).toBe(0);
    });
  });

  describe('show', () => {
    it('should GET /api/users/{user}', async () => {
      const mockUser = { id: 1, name: 'John', email: 'john@example.com' };

      nock(BASE_URL).get('/api/users/1').reply(200, mockUser);

      const result = await usersApi.show({ user: 1 });
      expect(result).toEqual(mockUser);
    });

    it('should handle string user id', async () => {
      const mockUser = { id: 1, name: 'John', email: 'john@example.com' };

      nock(BASE_URL).get('/api/users/abc-123').reply(200, mockUser);

      const result = await usersApi.show({ user: 'abc-123' });
      expect(result).toEqual(mockUser);
    });

    it('should throw 404 for non-existent user', async () => {
      nock(BASE_URL).get('/api/users/999').reply(404, { message: 'User not found' });

      await expect(usersApi.show({ user: 999 })).rejects.toMatchObject({
        status: 404,
        message: 'User not found',
      });
    });
  });

  describe('store', () => {
    it('should POST /api/users with body', async () => {
      const newUser = { name: 'John', email: 'john@example.com', password: 'secret123' };
      const createdUser = { id: 1, name: 'John', email: 'john@example.com' };

      nock(BASE_URL).post('/api/users', newUser).reply(201, createdUser);

      const result = await usersApi.store({ body: newUser });
      expect(result).toEqual(createdUser);
    });

    it('should handle validation errors', async () => {
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
        usersApi.store({ body: { name: '', email: '', password: '' } })
      ).rejects.toMatchObject({
        status: 422,
        message: 'The given data was invalid.',
        errors: {
          email: ['The email field is required.'],
          name: ['The name field is required.'],
        },
      });
    });

    it('should handle unique constraint error', async () => {
      nock(BASE_URL)
        .post('/api/users')
        .reply(422, {
          message: 'The given data was invalid.',
          errors: {
            email: ['The email has already been taken.'],
          },
        });

      await expect(
        usersApi.store({
          body: { name: 'John', email: 'existing@example.com', password: 'secret123' },
        })
      ).rejects.toMatchObject({
        status: 422,
        errors: {
          email: ['The email has already been taken.'],
        },
      });
    });
  });

  describe('update', () => {
    it('should PUT /api/users/{user} with body', async () => {
      const updates = { name: 'Updated John', email: null, avatar: null };
      const updatedUser = { id: 1, name: 'Updated John', email: 'john@example.com' };

      nock(BASE_URL).put('/api/users/1', updates).reply(200, updatedUser);

      const result = await usersApi.update({ user: 1, body: updates });
      expect(result).toEqual(updatedUser);
    });

    it('should handle partial updates', async () => {
      const updates = { email: 'new@example.com', name: null, avatar: null };
      const updatedUser = { id: 1, name: 'John', email: 'new@example.com' };

      nock(BASE_URL).put('/api/users/1', updates).reply(200, updatedUser);

      const result = await usersApi.update({ user: 1, body: updates });
      expect(result.email).toBe('new@example.com');
    });

    it('should throw 404 for non-existent user', async () => {
      nock(BASE_URL).put('/api/users/999').reply(404, { message: 'User not found' });

      await expect(
        usersApi.update({ user: 999, body: { name: 'Test', email: null, avatar: null } })
      ).rejects.toMatchObject({
        status: 404,
        message: 'User not found',
      });
    });

    it('should handle validation errors on update', async () => {
      nock(BASE_URL)
        .put('/api/users/1')
        .reply(422, {
          message: 'The given data was invalid.',
          errors: {
            email: ['The email must be a valid email address.'],
          },
        });

      await expect(
        usersApi.update({ user: 1, body: { email: 'invalid', name: null, avatar: null } })
      ).rejects.toMatchObject({
        status: 422,
        errors: {
          email: ['The email must be a valid email address.'],
        },
      });
    });
  });

  describe('destroy', () => {
    it('should DELETE /api/users/{user}', async () => {
      nock(BASE_URL).delete('/api/users/1').reply(204);

      const result = await usersApi.destroy({ user: 1 });
      expect(result).toBeUndefined();
    });

    it('should handle string user id', async () => {
      nock(BASE_URL).delete('/api/users/abc-123').reply(204);

      const result = await usersApi.destroy({ user: 'abc-123' });
      expect(result).toBeUndefined();
    });

    it('should throw 404 for non-existent user', async () => {
      nock(BASE_URL).delete('/api/users/999').reply(404, { message: 'User not found' });

      await expect(usersApi.destroy({ user: 999 })).rejects.toMatchObject({
        status: 404,
        message: 'User not found',
      });
    });

    it('should handle 403 forbidden', async () => {
      nock(BASE_URL)
        .delete('/api/users/1')
        .reply(403, { message: 'You are not authorized to delete this user.' });

      await expect(usersApi.destroy({ user: 1 })).rejects.toMatchObject({
        status: 403,
        message: 'You are not authorized to delete this user.',
      });
    });
  });

  describe('Request Options', () => {
    it('should pass custom headers', async () => {
      const mockUser = { id: 1, name: 'John', email: 'john@example.com' };

      nock(BASE_URL)
        .get('/api/users/1')
        .matchHeader('X-Request-Id', 'test-123')
        .reply(200, mockUser);

      const result = await usersApi.show({ user: 1 }, { headers: { 'X-Request-Id': 'test-123' } });
      expect(result).toEqual(mockUser);
    });

    it('should pass abort signal', async () => {
      const controller = new AbortController();
      const mockUser = { id: 1, name: 'John', email: 'john@example.com' };

      nock(BASE_URL).get('/api/users/1').reply(200, mockUser);

      const result = await usersApi.show({ user: 1 }, { signal: controller.signal });
      expect(result).toEqual(mockUser);
    });
  });
});
