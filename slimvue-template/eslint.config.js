import pluginVue from 'eslint-plugin-vue';
import eslintConfigPrettier from 'eslint-config-prettier';

export default [
    {
        ignores: ['coverage/**', 'dist/**', 'node_modules/**'],
    },
    ...pluginVue.configs['flat/recommended'],
    eslintConfigPrettier,
    {
        rules: {
            'vue/multi-word-component-names': 'off',
        },
    },
];
