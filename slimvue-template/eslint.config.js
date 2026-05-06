import js from '@eslint/js';
import pluginVue from 'eslint-plugin-vue';
import eslintConfigPrettier from 'eslint-config-prettier';

export default [
    {
        name: 'slimvue/ignores',
        ignores: ['coverage/**', 'dist/**', 'node_modules/**'],
    },
    js.configs.recommended,
    ...pluginVue.configs['flat/recommended'],
    eslintConfigPrettier,
    {
        name: 'slimvue/base-rules',
        rules: {
            'vue/multi-word-component-names': 'off',
            'no-unused-vars': ['error', { argsIgnorePattern: '^_' }],
            'no-undef': 'error',
            eqeqeq: ['error', 'always'],
            'no-var': 'error',
            'prefer-const': 'error',
            'no-implicit-globals': 'error',
            'no-shadow': 'error',
        },
    },
    // Node.js scripts & config files
    {
        name: 'slimvue/node-scripts',
        files: ['scripts/**/*.js', 'vite.config.js', 'postcss.config.cjs'],
        languageOptions: {
            globals: {
                process: 'readonly',
                console: 'readonly',
                __dirname: 'readonly',
                __filename: 'readonly',
                module: 'readonly',
                require: 'readonly',
            },
        },
        rules: {
            'no-unused-vars': ['error', { argsIgnorePattern: '^_', varsIgnorePattern: '^_' }],
        },
    },
    // Browser source files
    {
        name: 'slimvue/browser-source',
        files: ['src/**/*.js', 'src/**/*.vue', 'slimvue.js'],
        languageOptions: {
            globals: {
                window: 'readonly',
                document: 'readonly',
                console: 'readonly',
                setInterval: 'readonly',
                clearInterval: 'readonly',
                setTimeout: 'readonly',
                clearTimeout: 'readonly',
            },
        },
    },
    // Test files (vitest globals + browser/node)
    {
        name: 'slimvue/test-files',
        files: ['tests/**/*.js'],
        languageOptions: {
            globals: {
                // vitest globals
                describe: 'readonly',
                it: 'readonly',
                expect: 'readonly',
                beforeEach: 'readonly',
                afterEach: 'readonly',
                beforeAll: 'readonly',
                afterAll: 'readonly',
                vi: 'readonly',
                // browser
                window: 'readonly',
                document: 'readonly',
                console: 'readonly',
                // node
                process: 'readonly',
            },
        },
        rules: {
            'no-shadow': 'off',
            'no-unused-vars': ['error', { argsIgnorePattern: '^_', varsIgnorePattern: '^_' }],
        },
    },
];
