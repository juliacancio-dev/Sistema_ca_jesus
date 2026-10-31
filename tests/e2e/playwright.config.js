import { defineConfig, devices } from '@playwright/test';

export default defineConfig({
    testDir: './specs',
    // Os testes dividem um único banco: rodam um de cada vez.
    workers: 1,
    fullyParallel: false,
    timeout: 60_000,
    reporter: [['list']],
    use: {
        baseURL: 'http://localhost:8088/sistema_ca_jesus/public/',
        screenshot: 'only-on-failure',
        trace: 'retain-on-failure',
        locale: 'pt-BR',
        timezoneId: 'America/Sao_Paulo',
    },
    projects: [{ name: 'chromium', use: { ...devices['Desktop Chrome'] } }],
});
