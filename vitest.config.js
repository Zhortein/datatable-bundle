import { defineConfig } from 'vitest/config';

export default defineConfig({
    test: {
        environment: 'jsdom',
        globals: true,
        include: [
            'tests/Frontend/**/*.test.js'
        ],
        setupFiles: [
            'tests/Frontend/setup.js'
        ]
    }
});
