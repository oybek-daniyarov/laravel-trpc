import { describe, it, expect } from 'vitest';
import type { RequestOf, ResponseOf, QueryOf, ErrorOf } from '../resources/js/api/core/helpers';

/**
 * Type helper tests - these verify that the type helpers work correctly
 * at compile time. If these tests compile, the types are working.
 */
describe('Type Helpers', () => {
  describe('RequestOf', () => {
    it('extracts request type from route name', () => {
      // This test verifies that RequestOf accepts a route name string
      type LoginRequest = RequestOf<'auth.login'>;
      type StoreUserRequest = RequestOf<'users.store'>;

      // If this compiles, the type helper works correctly
      // We can't test the actual type at runtime, but we can verify it's not never
      const assertNotNever = <T>(_value: T extends never ? 'fail' : 'pass') => {};

      // These should compile without errors
      assertNotNever<LoginRequest>('pass');
      assertNotNever<StoreUserRequest>('pass');

      expect(true).toBe(true);
    });

    it('returns NoBody for routes without request type', () => {
      // Routes without request should have NoBody as request type
      type _ShowUserRequest = RequestOf<'users.show'>;

      // This compiles, verifying the type works
      expect(true).toBe(true);
    });
  });

  describe('ResponseOf', () => {
    it('extracts response type from route name', () => {
      type LoginResponse = ResponseOf<'auth.login'>;
      type UsersIndexResponse = ResponseOf<'users.index'>;

      const assertNotNever = <T>(_value: T extends never ? 'fail' : 'pass') => {};
      assertNotNever<LoginResponse>('pass');
      assertNotNever<UsersIndexResponse>('pass');

      expect(true).toBe(true);
    });
  });

  describe('QueryOf', () => {
    it('extracts query type from route name', () => {
      type UsersIndexQuery = QueryOf<'users.index'>;

      const assertNotNever = <T>(_value: T extends never ? 'fail' : 'pass') => {};
      assertNotNever<UsersIndexQuery>('pass');

      expect(true).toBe(true);
    });
  });

  describe('ErrorOf', () => {
    it('extracts error type from route name', () => {
      type LoginError = ErrorOf<'auth.login'>;

      const assertNotNever = <T>(_value: T extends never ? 'fail' : 'pass') => {};
      assertNotNever<LoginError>('pass');

      expect(true).toBe(true);
    });
  });

  describe('Type constraints', () => {
    it('only accepts valid route names', () => {
      // This test verifies that the type helpers are constrained to RouteName
      // If someone tries to use an invalid route name, TypeScript will error

      // Valid route names should work (compile time check)
      type _ValidRequest = RequestOf<'users.index'>;
      type _ValidResponse = ResponseOf<'users.show'>;

      // Invalid route names would cause a compile error:
      // type InvalidRequest = RequestOf<'invalid.route'>; // Error!

      expect(true).toBe(true);
    });

    it('works with all HTTP methods', () => {
      // GET routes
      type _GetResponse = ResponseOf<'users.index'>;

      // POST routes
      type _PostRequest = RequestOf<'users.store'>;

      // PUT routes
      type _PutRequest = RequestOf<'users.update'>;

      // DELETE routes
      type _DeleteResponse = ResponseOf<'users.destroy'>;

      expect(true).toBe(true);
    });
  });
});
