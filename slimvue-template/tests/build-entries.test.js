/**
 * Tests for scripts/entries.js — multi-page entry scanner (ESM / Vite).
 */

import { describe, test, expect, beforeEach, vi, afterEach } from 'vitest';
import fc from 'fast-check';
import { scanEntries } from '../scripts/entries.js';
import fs from 'fs';
import os from 'os';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const entryDir = path.resolve(__dirname, '../src/entries');
const templateDir = path.resolve(__dirname, '../template');

beforeEach(() => {
    // Reset env vars that affect scanning
    delete process.env.EXCLUED_ENTRIES;
    delete process.env.BUILD_FILE_TYPE;
});

describe('scanEntries()', () => {
    test('returns an object with pages and inputMap', () => {
        const result = scanEntries();
        expect(result).toHaveProperty('pages');
        expect(result).toHaveProperty('inputMap');
    });

    test('pages contains index entry', () => {
        const { pages } = scanEntries();
        expect(pages).toHaveProperty('index');
    });

    test('pages contains subpage-index entry', () => {
        const { pages } = scanEntries();
        expect(pages).toHaveProperty('subpage-index');
    });

    test('each page entry has entry, template, filename', () => {
        const { pages } = scanEntries();
        Object.values(pages).forEach((page) => {
            expect(page).toHaveProperty('entry');
            expect(page).toHaveProperty('template');
            expect(page).toHaveProperty('filename');
        });
    });

    test('entry paths end with .js', () => {
        const { pages } = scanEntries();
        Object.values(pages).forEach((page) => {
            expect(page.entry).toMatch(/\.js$/);
        });
    });

    test('filename uses twig extension by default', () => {
        const { pages } = scanEntries();
        Object.values(pages).forEach((page) => {
            expect(page.filename).toMatch(/\.twig$/);
        });
    });

    test('filename uses html extension when BUILD_FILE_TYPE=html', () => {
        process.env.BUILD_FILE_TYPE = 'html';
        const { pages } = scanEntries();
        Object.values(pages).forEach((page) => {
            expect(page.filename).toMatch(/\.html$/);
        });
    });

    test('template path includes buildFileType', () => {
        const { pages } = scanEntries();
        Object.values(pages).forEach((page) => {
            expect(page.template).toMatch(/index\.twig$/);
        });
    });

    test('excluded entries are filtered out', () => {
        const { pages } = scanEntries({
            excludedEntries: ['subpage'],
        });
        expect(pages).not.toHaveProperty('subpage-index');
        expect(pages).toHaveProperty('index');
    });

    test('EXCLUED_ENTRIES env var filters entries', () => {
        process.env.EXCLUED_ENTRIES = 'subpage';
        const { pages } = scanEntries();
        expect(pages).not.toHaveProperty('subpage-index');
        expect(pages).toHaveProperty('index');
    });

    test('entries include tdk metadata when provided', () => {
        const tdkMap = {
            index: {
                title: 'Home',
                keywords: 'home,test',
                description: 'Home page',
            },
        };
        const { pages } = scanEntries({ tdkMap });
        expect(pages.index).toHaveProperty('title', 'Home');
        expect(pages.index).toHaveProperty('keywords', 'home,test');
    });

    test('inputMap maps entry keys to file paths', () => {
        const { inputMap } = scanEntries();
        expect(inputMap).toHaveProperty('index');
        expect(inputMap.index).toMatch(/\.js$/);
        expect(inputMap).toHaveProperty('subpage-index');
    });

    test('returns empty pages when entryDir does not exist', () => {
        const { pages, inputMap } = scanEntries({
            entryDir: path.resolve(__dirname, '../nonexistent-dir'),
        });
        expect(Object.keys(pages)).toHaveLength(0);
        expect(Object.keys(inputMap)).toHaveLength(0);
    });
});

// ── Property-Based Tests ──

/**
 * Arbitrary for generating valid entry file names (lowercase alphanumeric, 1-12 chars).
 */
const entryNameArb = fc.stringMatching(/^[a-z][a-z0-9]{0,11}$/);

describe('PBT: Feature: release-4.0, Property 10: entry scanner correctness', () => {
    test('for any set of entry files and exclude list, scanner generates exactly one page per non-excluded file with correct naming', () => {
        fc.assert(
            fc.property(
                fc.uniqueArray(entryNameArb, { minLength: 1, maxLength: 8 }),
                fc.nat({ max: 7 }),
                (entryNames, excludeCount) => {
                    // Each iteration gets its own temp directory
                    const tmpDir = fs.mkdtempSync(
                        path.join(os.tmpdir(), 'entries-pbt-'),
                    );
                    try {
                        const entryDir = path.join(tmpDir, 'entries');
                        fs.mkdirSync(entryDir, { recursive: true });

                        // Create entries as subdirectories with index.js
                        // (matching real project structure: src/entries/<name>/index.js)
                        for (const name of entryNames) {
                            const subDir = path.join(entryDir, name);
                            fs.mkdirSync(subDir, { recursive: true });
                            fs.writeFileSync(
                                path.join(subDir, 'index.js'),
                                `export default {}`,
                            );
                        }

                        const tplDir = path.join(tmpDir, 'template');
                        fs.mkdirSync(tplDir, { recursive: true });
                        fs.writeFileSync(
                            path.join(tplDir, 'index.twig'),
                            '<html></html>',
                        );

                        // Pick a subset to exclude (by directory name)
                        const toExclude = entryNames.slice(
                            0,
                            Math.min(excludeCount, entryNames.length),
                        );
                        const expectedIncluded = entryNames.filter(
                            (n) => !toExclude.includes(n),
                        );

                        const { pages, inputMap } = scanEntries({
                            entryDir,
                            templateDir: tplDir,
                            excludedEntries: toExclude,
                        });

                        // (a) Exactly one page per non-excluded entry
                        expect(Object.keys(pages).length).toBe(
                            expectedIncluded.length,
                        );

                        // (b) No excluded entries appear
                        for (const excluded of toExclude) {
                            expect(pages).not.toHaveProperty(
                                `${excluded}-index`,
                            );
                        }

                        // (c) Output paths follow naming convention: pages/<name>/index.twig
                        for (const name of expectedIncluded) {
                            const key = `${name}-index`;
                            expect(pages).toHaveProperty(key);
                            expect(pages[key].filename).toBe(
                                `pages/${name}/index.twig`,
                            );
                        }

                        // inputMap matches pages
                        expect(Object.keys(inputMap).length).toBe(
                            expectedIncluded.length,
                        );
                    } finally {
                        fs.rmSync(tmpDir, { recursive: true, force: true });
                    }
                },
            ),
            { numRuns: 100 },
        );
    });
});

describe('PBT: Feature: release-4.0, Property 11: add entry increases page count', () => {
    test('adding one new .js file increases page count by exactly 1', () => {
        fc.assert(
            fc.property(
                fc.uniqueArray(entryNameArb, { minLength: 1, maxLength: 6 }),
                entryNameArb,
                (existingNames, newName) => {
                    // Ensure newName is not in existingNames
                    fc.pre(!existingNames.includes(newName));

                    const tmpDir = fs.mkdtempSync(
                        path.join(os.tmpdir(), 'entries-pbt-'),
                    );
                    try {
                        const entryDir = path.join(tmpDir, 'entries');
                        const tplDir = path.join(tmpDir, 'template');
                        fs.mkdirSync(entryDir, { recursive: true });
                        fs.mkdirSync(tplDir, { recursive: true });
                        fs.writeFileSync(
                            path.join(tplDir, 'index.twig'),
                            '<html></html>',
                        );

                        // Create initial entries as subdirectories
                        for (const name of existingNames) {
                            const subDir = path.join(entryDir, name);
                            fs.mkdirSync(subDir, { recursive: true });
                            fs.writeFileSync(
                                path.join(subDir, 'index.js'),
                                `export default {}`,
                            );
                        }

                        const before = scanEntries({
                            entryDir,
                            templateDir: tplDir,
                        });
                        const countBefore = Object.keys(before.pages).length;

                        // Add one new entry as subdirectory
                        const newDir = path.join(entryDir, newName);
                        fs.mkdirSync(newDir, { recursive: true });
                        fs.writeFileSync(
                            path.join(newDir, 'index.js'),
                            `export default {}`,
                        );

                        const after = scanEntries({
                            entryDir,
                            templateDir: tplDir,
                        });
                        const countAfter = Object.keys(after.pages).length;

                        expect(countAfter).toBe(countBefore + 1);
                    } finally {
                        fs.rmSync(tmpDir, { recursive: true, force: true });
                    }
                },
            ),
            { numRuns: 100 },
        );
    });
});
