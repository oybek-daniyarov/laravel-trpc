import { afterEach, beforeEach, vi } from 'vitest';
import nock from 'nock';

// Enable nock for all tests
beforeEach(() => {
  nock.disableNetConnect();
});

// Clean up nock after each test
afterEach(() => {
  nock.cleanAll();
  nock.enableNetConnect();
  vi.restoreAllMocks();
});

// Mock document.cookie for CSRF tests
Object.defineProperty(document, 'cookie', {
  writable: true,
  value: '',
});
