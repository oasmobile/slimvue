/**
 * Tests for scripts/entries.js — multi-page entry scanner (ESM / Vite).
 */

import { describe, test, expect, beforeEach, vi } from 'vitest';
import { scanEntries } from '../scripts/entries.js';
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
