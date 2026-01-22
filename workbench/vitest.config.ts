import { defineConfig } from 'vitest/config';

export default defineConfig({
  test: {
    globals: true,
    environment: 'happy-dom',
    setupFiles: ['./vitest.setup.ts'],
    include: ['__tests__/**/*.test.ts'],
    coverage: {
      provider: 'v8',
      include: ['resources/js/api/**/*.ts'],
      exclude: ['resources/js/api/**/*.d.ts', 'resources/js/api/**/index.ts'],
    },
  },
});
