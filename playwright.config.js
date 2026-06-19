const { defineConfig } = require('@playwright/test');

const mockPort = process.env.MOCK_INGEST_PORT || 9911;

module.exports = defineConfig({
  testDir: './e2e',
  timeout: 30000,
  workers: 1,
  reporter: [['list'], ['json', { outputFile: 'test-results/results.json' }]],
  use: {
    baseURL: process.env.WP_BASE_URL || 'http://localhost:8888',
    trace: 'retain-on-failure',
  },
  webServer: {
    command: 'node e2e/mock-ingest.cjs',
    url: `http://localhost:${mockPort}/__events`,
    reuseExistingServer: true,
    timeout: 10000,
  },
});
