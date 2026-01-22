import { describe, it, expect } from 'vitest';
import { createApi } from '../resources/js/api/api';
import { createMutations } from '../resources/js/api/mutations';

const mockConfig = {
  baseUrl: 'https://api.example.com/',
};

describe('createMutations', () => {
  const api = createApi(mockConfig);
  const mutations = createMutations(api);

  it('should return mutations object with all groups', () => {
    expect(mutations).toHaveProperty('users');
  });

  it('should have keys property for each group', () => {
    expect(mutations.users).toHaveProperty('keys');
    expect(mutations.users.keys).toHaveProperty('all');
    expect(mutations.users.keys.all).toEqual(['users', 'mutation']);
  });
});

describe('usersMutations', () => {
  const api = createApi(mockConfig);
  const mutations = createMutations(api);

  it('should have store mutation', () => {
    expect(mutations.users).toHaveProperty('store');
    expect(typeof mutations.users.store).toBe('function');
  });

  it('should have update mutation', () => {
    expect(mutations.users).toHaveProperty('update');
    expect(typeof mutations.users.update).toBe('function');
  });

  it('should have destroy mutation', () => {
    expect(mutations.users).toHaveProperty('destroy');
    expect(typeof mutations.users.destroy).toBe('function');
  });

  describe('store mutation options', () => {
    it('should return valid mutation options object', () => {
      const options = mutations.users.store();

      expect(options).toHaveProperty('mutationKey');
      expect(options).toHaveProperty('mutationFn');
    });

    it('should have correct mutationKey', () => {
      const options = mutations.users.store();
      expect(options.mutationKey).toEqual(['users.store']);
    });

    it('should have mutationFn as function', () => {
      const options = mutations.users.store();
      expect(typeof options.mutationFn).toBe('function');
    });
  });

  describe('update mutation options', () => {
    it('should return valid mutation options object', () => {
      const options = mutations.users.update();

      expect(options).toHaveProperty('mutationKey');
      expect(options).toHaveProperty('mutationFn');
    });

    it('should have correct mutationKey', () => {
      const options = mutations.users.update();
      expect(options.mutationKey).toEqual(['users.update']);
    });
  });

  describe('destroy mutation options', () => {
    it('should return valid mutation options object', () => {
      const options = mutations.users.destroy();

      expect(options).toHaveProperty('mutationKey');
      expect(options).toHaveProperty('mutationFn');
    });

    it('should have correct mutationKey', () => {
      const options = mutations.users.destroy();
      expect(options.mutationKey).toEqual(['users.destroy']);
    });
  });
});

describe('mutation keys', () => {
  const api = createApi(mockConfig);
  const mutations = createMutations(api);

  it('should generate unique keys for different mutations', () => {
    const storeKey = mutations.users.keys.store();
    const updateKey = mutations.users.keys.update();
    const destroyKey = mutations.users.keys.destroy();

    expect(storeKey).not.toEqual(updateKey);
    expect(storeKey).not.toEqual(destroyKey);
    expect(updateKey).not.toEqual(destroyKey);
  });

  it('should have correct format for mutation keys', () => {
    expect(mutations.users.keys.store()).toEqual(['users.store']);
    expect(mutations.users.keys.update()).toEqual(['users.update']);
    expect(mutations.users.keys.destroy()).toEqual(['users.destroy']);
  });
});
