import { describe, it, expectTypeOf } from 'vitest';
import type { RequestOf, ResponseOf, QueryOf, ErrorOf } from '../resources/js/api/core/helpers';
import type { NoBody, ValidationError, PaginatedResponse } from '../resources/js/api/core/types';

describe('Type Helpers', () => {
  describe('RequestOf', () => {
    it('extracts CreateUserData for users.store', () => {
      expectTypeOf<RequestOf<'users.store'>>().toEqualTypeOf<Workbench.App.Data.CreateUserData>();
    });

    it('extracts UpdateUserData for users.update', () => {
      expectTypeOf<RequestOf<'users.update'>>().toEqualTypeOf<Workbench.App.Data.UpdateUserData>();
    });

    it('extracts LoginData for auth.login', () => {
      expectTypeOf<RequestOf<'auth.login'>>().toEqualTypeOf<Workbench.App.Data.LoginData>();
    });

    it('returns NoBody for GET routes', () => {
      expectTypeOf<RequestOf<'users.show'>>().toEqualTypeOf<NoBody>();
      expectTypeOf<RequestOf<'users.index'>>().toEqualTypeOf<Workbench.App.Data.UserQueryData>();
    });
  });

  describe('ResponseOf', () => {
    it('extracts UserData for users.show', () => {
      expectTypeOf<ResponseOf<'users.show'>>().toEqualTypeOf<Workbench.App.Data.UserData>();
    });

    it('extracts PaginatedResponse<UserData> for users.index', () => {
      expectTypeOf<ResponseOf<'users.index'>>().toEqualTypeOf<
        PaginatedResponse<Workbench.App.Data.UserData>
      >();
    });

    it('extracts UserData for users.store', () => {
      expectTypeOf<ResponseOf<'users.store'>>().toEqualTypeOf<Workbench.App.Data.UserData>();
    });
  });

  describe('QueryOf', () => {
    it('extracts UserQueryData for users.index', () => {
      expectTypeOf<QueryOf<'users.index'>>().toEqualTypeOf<Workbench.App.Data.UserQueryData>();
    });

    it('returns NoBody for routes without query params', () => {
      expectTypeOf<QueryOf<'users.show'>>().toEqualTypeOf<NoBody>();
      expectTypeOf<QueryOf<'users.store'>>().toEqualTypeOf<NoBody>();
    });
  });

  describe('ErrorOf', () => {
    it('returns ValidationError as default error type', () => {
      expectTypeOf<ErrorOf<'users.store'>>().toEqualTypeOf<ValidationError>();
      expectTypeOf<ErrorOf<'users.update'>>().toEqualTypeOf<ValidationError>();
      expectTypeOf<ErrorOf<'auth.login'>>().toEqualTypeOf<ValidationError>();
    });
  });

  describe('Type constraints', () => {
    it('works with all HTTP methods', () => {
      // GET - users.show
      expectTypeOf<ResponseOf<'users.show'>>().toEqualTypeOf<Workbench.App.Data.UserData>();

      // POST - users.store
      expectTypeOf<RequestOf<'users.store'>>().toEqualTypeOf<Workbench.App.Data.CreateUserData>();

      // PUT - users.update
      expectTypeOf<RequestOf<'users.update'>>().toEqualTypeOf<Workbench.App.Data.UpdateUserData>();

      // DELETE - users.destroy returns unknown (no typed response)
      expectTypeOf<ResponseOf<'users.destroy'>>().toEqualTypeOf<unknown>();
    });
  });
});
