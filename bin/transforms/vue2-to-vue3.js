#!/usr/bin/env node

/**
 * AST-based Vue 2 → Vue 3 code transformation script.
 *
 * Transforms:
 *   - `new Vue(` → `createApp(`
 *   - `Vue.prototype` → `app.config.globalProperties`
 *
 * Only transforms actual code — comments and strings are preserved.
 *
 * Usage:
 *   node vue2-to-vue3.js <project-dir>
 *   node vue2-to-vue3.js <file1.js> <file2.js> ...
 *
 * Exit codes:
 *   0 — success (files transformed or no changes needed)
 *   1 — error (invalid arguments, file read/write failure)
 */

import { readFileSync, writeFileSync, readdirSync, statSync } from 'fs';
import { join, extname, resolve } from 'path';

// ── Token-based AST-aware transformer ──

/**
 * Tokenize JS source into segments of code, comments, and strings.
 * Each token has: { type: 'code'|'line_comment'|'block_comment'|'string'|'template', value: string }
 */
function tokenize(source) {
    const tokens = [];
    let i = 0;
    let codeStart = 0;

    function pushCode(end) {
        if (end > codeStart) {
            tokens.push({ type: 'code', value: source.slice(codeStart, end) });
        }
    }

    while (i < source.length) {
        const ch = source[i];
        const next = source[i + 1];

        // Line comment
        if (ch === '/' && next === '/') {
            pushCode(i);
            const start = i;
            i += 2;
            while (i < source.length && source[i] !== '\n') {
                i++;
            }
            tokens.push({ type: 'line_comment', value: source.slice(start, i) });
            codeStart = i;
            continue;
        }

        // Block comment
        if (ch === '/' && next === '*') {
            pushCode(i);
            const start = i;
            i += 2;
            while (i < source.length - 1 && !(source[i] === '*' && source[i + 1] === '/')) {
                i++;
            }
            i += 2; // skip */
            tokens.push({ type: 'block_comment', value: source.slice(start, i) });
            codeStart = i;
            continue;
        }

        // Template literal
        if (ch === '`') {
            pushCode(i);
            const start = i;
            i++;
            while (i < source.length && source[i] !== '`') {
                if (source[i] === '\\') {
                    i++; // skip escaped char
                }
                i++;
            }
            i++; // skip closing `
            tokens.push({ type: 'template', value: source.slice(start, i) });
            codeStart = i;
            continue;
        }

        // Double-quoted string
        if (ch === '"') {
            pushCode(i);
            const start = i;
            i++;
            while (i < source.length && source[i] !== '"') {
                if (source[i] === '\\') {
                    i++; // skip escaped char
                }
                i++;
            }
            i++; // skip closing "
            tokens.push({ type: 'string', value: source.slice(start, i) });
            codeStart = i;
            continue;
        }

        // Single-quoted string
        if (ch === "'") {
            pushCode(i);
            const start = i;
            i++;
            while (i < source.length && source[i] !== "'") {
                if (source[i] === '\\') {
                    i++; // skip escaped char
                }
                i++;
            }
            i++; // skip closing '
            tokens.push({ type: 'string', value: source.slice(start, i) });
            codeStart = i;
            continue;
        }

        // Regex literal — simplified detection (after operator or at start of expression)
        // Skip regex detection to avoid complexity; regex literals rarely contain Vue patterns

        i++;
    }

    // Remaining code
    pushCode(source.length);

    return tokens;
}

/**
 * Transform code tokens only, preserving comments and strings.
 *
 * Returns { output: string, changes: string[] }
 */
function transformSource(source) {
    const tokens = tokenize(source);
    const changes = [];
    let modified = false;

    const transformed = tokens.map((token) => {
        if (token.type !== 'code') {
            return token.value;
        }

        let code = token.value;

        // Transform: new Vue( → createApp(
        const newVuePattern = /\bnew\s+Vue\s*\(/g;
        if (newVuePattern.test(code)) {
            const count = (code.match(newVuePattern) || []).length;
            code = code.replace(newVuePattern, 'createApp(');
            changes.push(`new Vue( → createApp( (${count} occurrence${count > 1 ? 's' : ''})`);
            modified = true;
        }

        // Transform: Vue.prototype → app.config.globalProperties
        const prototypePattern = /\bVue\.prototype\b/g;
        if (prototypePattern.test(code)) {
            const count = (code.match(prototypePattern) || []).length;
            code = code.replace(prototypePattern, 'app.config.globalProperties');
            changes.push(`Vue.prototype → app.config.globalProperties (${count} occurrence${count > 1 ? 's' : ''})`);
            modified = true;
        }

        return code;
    });

    return {
        output: transformed.join(''),
        changes,
        modified,
    };
}

// ── File discovery ──

const JS_EXTENSIONS = new Set(['.js', '.vue', '.ts', '.jsx', '.tsx']);
const SKIP_DIRS = new Set(['node_modules', 'vendor', '.git', 'dist', 'coverage']);

function findJsFiles(dir) {
    const files = [];

    function walk(currentDir) {
        let entries;
        try {
            entries = readdirSync(currentDir);
        } catch {
            return;
        }

        for (const entry of entries) {
            if (SKIP_DIRS.has(entry)) {
                continue;
            }
            const fullPath = join(currentDir, entry);
            let stat;
            try {
                stat = statSync(fullPath);
            } catch {
                continue;
            }

            if (stat.isDirectory()) {
                walk(fullPath);
            } else if (stat.isFile() && JS_EXTENSIONS.has(extname(entry))) {
                files.push(fullPath);
            }
        }
    }

    walk(dir);
    return files;
}

// ── Main ──

function main() {
    const args = process.argv.slice(2);

    if (args.length === 0) {
        process.stderr.write('Usage: vue2-to-vue3.js <project-dir> | <file1.js> [file2.js ...]\n');
        process.exit(1);
    }

    // Determine if argument is a directory or file list
    let files;
    const firstArg = resolve(args[0]);
    let stat;
    try {
        stat = statSync(firstArg);
    } catch {
        process.stderr.write(`Error: '${args[0]}' does not exist\n`);
        process.exit(1);
    }

    if (stat.isDirectory()) {
        files = findJsFiles(firstArg);
    } else {
        files = args.map((a) => resolve(a));
    }

    if (files.length === 0) {
        process.stdout.write('No JS/Vue files found to transform\n');
        process.exit(0);
    }

    let totalChanges = 0;
    const results = [];

    for (const file of files) {
        let content;
        try {
            content = readFileSync(file, 'utf-8');
        } catch (err) {
            process.stderr.write(`Warning: Could not read ${file}: ${err.message}\n`);
            continue;
        }

        const { output, changes, modified } = transformSource(content);

        if (modified) {
            try {
                writeFileSync(file, output, 'utf-8');
                results.push({ file, changes });
                totalChanges += changes.length;
            } catch (err) {
                process.stderr.write(`Error: Could not write ${file}: ${err.message}\n`);
            }
        }
    }

    // Output results as JSON for the PHP caller to parse
    const report = {
        filesScanned: files.length,
        filesModified: results.length,
        totalChanges,
        details: results.map((r) => ({
            file: r.file,
            changes: r.changes,
        })),
    };

    process.stdout.write(JSON.stringify(report, null, 2) + '\n');
    process.exit(0);
}

// Export for testing
export { tokenize, transformSource, findJsFiles };

// Only run main() when executed directly (not when imported as a module)
// Detect by checking if this file is the entry point
const isMainModule = process.argv[1] && resolve(process.argv[1]) === resolve(new URL(import.meta.url).pathname);
if (isMainModule) {
    main();
}
