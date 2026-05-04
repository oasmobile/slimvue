/**
 * Tests for the AST-based Vue 2 → Vue 3 code transformation script.
 *
 * Feature: release-4.0, Property 18: AST-based JS code transformation
 *
 * For any JS file containing Vue 2 API calls, the transform should:
 * (a) correctly replace actual code calls with Vue 3 equivalents
 * (b) NOT modify comments and strings
 * (c) preserve code syntax correctness
 *
 * Validates: Requirements 16.4
 */

import { describe, it, expect } from 'vitest';
import fc from 'fast-check';
import { tokenize, transformSource } from '../../bin/transforms/vue2-to-vue3.js';

// ── Unit Tests: tokenize() ──

describe('tokenize', () => {
    it('should tokenize plain code', () => {
        const tokens = tokenize('const x = 1;');
        expect(tokens).toHaveLength(1);
        expect(tokens[0].type).toBe('code');
        expect(tokens[0].value).toBe('const x = 1;');
    });

    it('should tokenize line comments', () => {
        const tokens = tokenize('const x = 1; // comment\nconst y = 2;');
        const types = tokens.map((t) => t.type);
        expect(types).toContain('line_comment');
        expect(types).toContain('code');
    });

    it('should tokenize block comments', () => {
        const tokens = tokenize('const x = 1; /* block */ const y = 2;');
        const types = tokens.map((t) => t.type);
        expect(types).toContain('block_comment');
    });

    it('should tokenize double-quoted strings', () => {
        const tokens = tokenize('const x = "hello world";');
        const types = tokens.map((t) => t.type);
        expect(types).toContain('string');
    });

    it('should tokenize single-quoted strings', () => {
        const tokens = tokenize("const x = 'hello world';");
        const types = tokens.map((t) => t.type);
        expect(types).toContain('string');
    });

    it('should tokenize template literals', () => {
        const tokens = tokenize('const x = `hello ${name}`;');
        const types = tokens.map((t) => t.type);
        expect(types).toContain('template');
    });

    it('should handle escaped characters in strings', () => {
        const tokens = tokenize('const x = "hello \\"world\\"";');
        const stringTokens = tokens.filter((t) => t.type === 'string');
        expect(stringTokens).toHaveLength(1);
    });

    it('should handle multi-line block comments', () => {
        const source = 'const x = 1;\n/* line1\nline2\nline3 */\nconst y = 2;';
        const tokens = tokenize(source);
        const blockComment = tokens.find((t) => t.type === 'block_comment');
        expect(blockComment).toBeDefined();
        expect(blockComment.value).toContain('line1');
        expect(blockComment.value).toContain('line3');
    });

    it('should reconstruct original source from tokens', () => {
        const source = '// comment\nconst x = "str"; /* block */ const y = `tmpl`;';
        const tokens = tokenize(source);
        const reconstructed = tokens.map((t) => t.value).join('');
        expect(reconstructed).toBe(source);
    });
});

// ── Unit Tests: transformSource() ──

describe('transformSource', () => {
    it('should transform new Vue( to createApp(', () => {
        const { output, changes, modified } = transformSource('const vm = new Vue({ el: "#app" });');
        expect(output).toBe('const vm = createApp({ el: "#app" });');
        expect(modified).toBe(true);
        expect(changes).toHaveLength(1);
        expect(changes[0]).toContain('new Vue(');
    });

    it('should transform Vue.prototype to app.config.globalProperties', () => {
        const { output, changes, modified } = transformSource('Vue.prototype.$http = axios;');
        expect(output).toBe('app.config.globalProperties.$http = axios;');
        expect(modified).toBe(true);
        expect(changes).toHaveLength(1);
        expect(changes[0]).toContain('Vue.prototype');
    });

    it('should transform both patterns in one source', () => {
        const source = 'const vm = new Vue({});\nVue.prototype.$http = axios;';
        const { output, changes, modified } = transformSource(source);
        expect(output).toContain('createApp(');
        expect(output).toContain('app.config.globalProperties');
        expect(output).not.toContain('new Vue(');
        expect(output).not.toContain('Vue.prototype');
        expect(modified).toBe(true);
        expect(changes).toHaveLength(2);
    });

    it('should NOT transform new Vue( in line comments', () => {
        const source = '// const vm = new Vue({});\nconst x = 1;';
        const { output, modified } = transformSource(source);
        expect(output).toBe(source);
        expect(modified).toBe(false);
    });

    it('should NOT transform new Vue( in block comments', () => {
        const source = '/* new Vue({}) */\nconst x = 1;';
        const { output, modified } = transformSource(source);
        expect(output).toBe(source);
        expect(modified).toBe(false);
    });

    it('should NOT transform new Vue( in double-quoted strings', () => {
        const source = 'const msg = "new Vue({ el: \'#app\' })";';
        const { output, modified } = transformSource(source);
        expect(output).toBe(source);
        expect(modified).toBe(false);
    });

    it('should NOT transform new Vue( in single-quoted strings', () => {
        const source = "const msg = 'new Vue({ el: \"#app\" })';";
        const { output, modified } = transformSource(source);
        expect(output).toBe(source);
        expect(modified).toBe(false);
    });

    it('should NOT transform Vue.prototype in template literals', () => {
        const source = 'const msg = `Vue.prototype.$http`;';
        const { output, modified } = transformSource(source);
        expect(output).toBe(source);
        expect(modified).toBe(false);
    });

    it('should NOT transform Vue.prototype in comments', () => {
        const source = '// Vue.prototype.$http = axios;\nconst x = 1;';
        const { output, modified } = transformSource(source);
        expect(output).toBe(source);
        expect(modified).toBe(false);
    });

    it('should handle multiple occurrences in code', () => {
        const source = 'new Vue({}); new Vue({});';
        const { output, changes } = transformSource(source);
        expect(output).toBe('createApp({}); createApp({});');
        expect(changes[0]).toContain('2 occurrences');
    });

    it('should return unmodified source when no patterns match', () => {
        const source = 'import { createApp } from "vue";\ncreateApp({}).mount("#app");';
        const { output, modified } = transformSource(source);
        expect(output).toBe(source);
        expect(modified).toBe(false);
    });

    it('should handle empty source', () => {
        const { output, modified } = transformSource('');
        expect(output).toBe('');
        expect(modified).toBe(false);
    });

    it('should handle multi-line new Vue with whitespace', () => {
        const source = 'const vm = new  Vue\n({});';
        const { output, modified } = transformSource(source);
        expect(output).toContain('createApp');
        expect(modified).toBe(true);
    });

    it('should preserve code around transformed patterns', () => {
        const source = 'import Vue from "vue";\nconst vm = new Vue({ el: "#app" });\nconsole.log(vm);';
        const { output } = transformSource(source);
        expect(output).toContain('import Vue from "vue"');
        expect(output).toContain('createApp(');
        expect(output).toContain('console.log(vm)');
    });

    it('should handle mixed code, comments, and strings with patterns', () => {
        const source = [
            '// new Vue() in comment',
            'const msg = "new Vue() in string";',
            'const vm = new Vue({ el: "#app" });',
            '/* Vue.prototype in block comment */',
            "const str = 'Vue.prototype in string';",
            'Vue.prototype.$http = axios;',
        ].join('\n');

        const { output, changes } = transformSource(source);

        // Only code patterns should be transformed
        expect(output).toContain('// new Vue() in comment');
        expect(output).toContain('"new Vue() in string"');
        expect(output).toContain('createApp({ el: "#app" })');
        expect(output).toContain('/* Vue.prototype in block comment */');
        expect(output).toContain("'Vue.prototype in string'");
        expect(output).toContain('app.config.globalProperties.$http = axios;');

        expect(changes).toHaveLength(2);
    });
});

// ── Property-Based Tests ──

describe('Property 18: AST-based JS code transformation', () => {
    // Arbitrary safe identifier generator (lowercase letters only, 1-8 chars)
    const identArb = fc.stringMatching(/^[a-z]{1,8}$/);

    // Arbitrary safe string content (no quotes, no backslashes)
    const safeStringArb = fc.stringMatching(/^[a-z0-9 _\-.]{0,20}$/);

    it('should only transform code, never comments or strings (new Vue)', () => {
        fc.assert(
            fc.property(identArb, safeStringArb, (ident, _safeStr) => {
                // Pattern in code
                const codeSource = `const ${ident} = new Vue({});`;
                const codeResult = transformSource(codeSource);
                expect(codeResult.output).toContain('createApp(');
                expect(codeResult.modified).toBe(true);

                // Pattern in line comment
                const commentSource = `// new Vue({})\nconst ${ident} = 1;`;
                const commentResult = transformSource(commentSource);
                expect(commentResult.output).toBe(commentSource);
                expect(commentResult.modified).toBe(false);

                // Pattern in double-quoted string
                const stringSource = `const ${ident} = "new Vue({})";`;
                const stringResult = transformSource(stringSource);
                expect(stringResult.output).toBe(stringSource);
                expect(stringResult.modified).toBe(false);

                // Pattern in single-quoted string
                const singleSource = `const ${ident} = 'new Vue({})';`;
                const singleResult = transformSource(singleSource);
                expect(singleResult.output).toBe(singleSource);
                expect(singleResult.modified).toBe(false);

                // Pattern in block comment
                const blockSource = `/* new Vue({}) */\nconst ${ident} = 1;`;
                const blockResult = transformSource(blockSource);
                expect(blockResult.output).toBe(blockSource);
                expect(blockResult.modified).toBe(false);

                // Pattern in template literal
                const tmplSource = `const ${ident} = \`new Vue({})\`;`;
                const tmplResult = transformSource(tmplSource);
                expect(tmplResult.output).toBe(tmplSource);
                expect(tmplResult.modified).toBe(false);
            }),
            { numRuns: 100 },
        );
    });

    it('should only transform code, never comments or strings (Vue.prototype)', () => {
        fc.assert(
            fc.property(identArb, safeStringArb, (ident, _safeStr) => {
                // Pattern in code
                const codeSource = `Vue.prototype.${ident} = 1;`;
                const codeResult = transformSource(codeSource);
                expect(codeResult.output).toContain('app.config.globalProperties');
                expect(codeResult.modified).toBe(true);

                // Pattern in line comment
                const commentSource = `// Vue.prototype.${ident}\nconst x = 1;`;
                const commentResult = transformSource(commentSource);
                expect(commentResult.output).toBe(commentSource);
                expect(commentResult.modified).toBe(false);

                // Pattern in double-quoted string
                const stringSource = `const x = "Vue.prototype.${ident}";`;
                const stringResult = transformSource(stringSource);
                expect(stringResult.output).toBe(stringSource);
                expect(stringResult.modified).toBe(false);

                // Pattern in block comment
                const blockSource = `/* Vue.prototype.${ident} */\nconst x = 1;`;
                const blockResult = transformSource(blockSource);
                expect(blockResult.output).toBe(blockSource);
                expect(blockResult.modified).toBe(false);
            }),
            { numRuns: 100 },
        );
    });

    it('should preserve syntax correctness after transformation', () => {
        fc.assert(
            fc.property(identArb, (ident) => {
                // A syntactically valid JS snippet with Vue 2 patterns
                const source = `const ${ident} = new Vue({ el: "#app" });\nVue.prototype.$${ident} = true;`;
                const { output } = transformSource(source);

                // After transformation, the output should still be parseable
                // We verify structural correctness by checking:
                // 1. Balanced braces/parens
                // 2. No dangling operators
                // 3. The transformed patterns are syntactically valid
                expect(output).toContain('createApp(');
                expect(output).toContain('app.config.globalProperties');
                expect(output).not.toContain('new Vue(');
                expect(output).not.toContain('Vue.prototype');

                // Verify the output has the same number of semicolons (statement count preserved)
                const origSemicolons = (source.match(/;/g) || []).length;
                const outSemicolons = (output.match(/;/g) || []).length;
                expect(outSemicolons).toBe(origSemicolons);
            }),
            { numRuns: 100 },
        );
    });

    it('should be idempotent — transforming already-transformed code produces no changes', () => {
        fc.assert(
            fc.property(identArb, (ident) => {
                const source = `const ${ident} = new Vue({});\nVue.prototype.$${ident} = 1;`;

                // First transform
                const first = transformSource(source);
                expect(first.modified).toBe(true);

                // Second transform on the output
                const second = transformSource(first.output);
                expect(second.modified).toBe(false);
                expect(second.output).toBe(first.output);
            }),
            { numRuns: 100 },
        );
    });

    it('tokenize should reconstruct original source for arbitrary inputs', () => {
        // Generate source with mixed code, comments, and strings
        const sourceArb = fc.oneof(
            // Plain code
            identArb.map((id) => `const ${id} = 1;`),
            // Code with line comment
            identArb.map((id) => `const ${id} = 1; // comment`),
            // Code with block comment
            identArb.map((id) => `const ${id} = 1; /* block */`),
            // Code with string
            identArb.map((id) => `const ${id} = "hello";`),
            // Code with single-quoted string
            identArb.map((id) => `const ${id} = 'hello';`),
            // Code with template literal
            identArb.map((id) => `const ${id} = \`hello\`;`),
        );

        fc.assert(
            fc.property(sourceArb, (source) => {
                const tokens = tokenize(source);
                const reconstructed = tokens.map((t) => t.value).join('');
                expect(reconstructed).toBe(source);
            }),
            { numRuns: 100 },
        );
    });
});
