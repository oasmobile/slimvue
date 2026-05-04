import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import { resolve } from 'path';
import { scanEntries } from './scripts/entries.js';
import { tdkPlugin, defaultTdkMap } from './scripts/tdk.js';
import { checkNodeVersion } from './scripts/check-node.js';

// Node.js version check
checkNodeVersion(24);

export default defineConfig(({ mode: _mode }) => {
    const { inputMap } = scanEntries({ tdkMap: defaultTdkMap });

    return {
        plugins: [vue(), tdkPlugin(defaultTdkMap)],
        resolve: {
            alias: {
                '@': resolve(__dirname, 'src'),
                slimvue: resolve(__dirname, 'slimvue.js'),
                assets: resolve(__dirname, 'src/assets'),
            },
        },
        build: {
            rollupOptions: {
                input: inputMap,
            },
            outDir: process.env.OUTPUT_DIR || 'dist',
        },
        base: process.env.PUBLIC_PATH || '/',
        test: {
            environment: 'jsdom',
            globals: true,
            coverage: {
                provider: 'v8',
                reporter: ['text', 'lcov', 'clover'],
            },
        },
    };
});
