/**
 * Tests for scripts/tdk.js — TDK metadata injection Vite plugin (ESM).
 */

import { describe, test, expect } from 'vitest';
import fc from 'fast-check';
import { defaultTdkMap, tdkPlugin } from '../scripts/tdk.js';

// ── defaultTdkMap ──

describe('defaultTdkMap', () => {
    test('exports an object', () => {
        expect(typeof defaultTdkMap).toBe('object');
    });

    test('has index page entry', () => {
        expect(defaultTdkMap).toHaveProperty('index');
        expect(defaultTdkMap.index).toHaveProperty('title');
        expect(defaultTdkMap.index).toHaveProperty('keywords');
        expect(defaultTdkMap.index).toHaveProperty('description');
    });

    test('has subpage-index entry', () => {
        expect(defaultTdkMap).toHaveProperty('subpage-index');
        expect(defaultTdkMap['subpage-index']).toHaveProperty('title');
    });

    test('index title is a non-empty string', () => {
        expect(typeof defaultTdkMap.index.title).toBe('string');
        expect(defaultTdkMap.index.title.length).toBeGreaterThan(0);
    });
});

// ── tdkPlugin ──

describe('tdkPlugin()', () => {
    test('returns a Vite plugin object', () => {
        const plugin = tdkPlugin();
        expect(plugin).toHaveProperty('name', 'slimvue-tdk');
        expect(typeof plugin.transformIndexHtml).toBe('function');
    });

    test('injects title into HTML', () => {
        const plugin = tdkPlugin({
            mypage: { title: 'My Title', keywords: '', description: '' },
        });
        const html = '<html><head><title></title></head><body></body></html>';
        const result = plugin.transformIndexHtml(html, {
            chunk: { name: 'mypage' },
        });
        expect(result).toContain('<title>My Title</title>');
    });

    test('injects keywords into HTML', () => {
        const plugin = tdkPlugin({
            mypage: { title: '', keywords: 'a,b,c', description: '' },
        });
        const html = '<html><head><!-- TDK_KEYWORDS --></head></html>';
        const result = plugin.transformIndexHtml(html, {
            chunk: { name: 'mypage' },
        });
        expect(result).toContain('<meta name="keywords" content="a,b,c">');
    });

    test('injects description into HTML', () => {
        const plugin = tdkPlugin({
            mypage: { title: '', keywords: '', description: 'A page' },
        });
        const html = '<html><head><!-- TDK_DESCRIPTION --></head></html>';
        const result = plugin.transformIndexHtml(html, {
            chunk: { name: 'mypage' },
        });
        expect(result).toContain(
            '<meta name="description" content="A page">',
        );
    });

    test('returns html unchanged when entry has no tdk', () => {
        const plugin = tdkPlugin({});
        const html = '<html><head><title>X</title></head></html>';
        const result = plugin.transformIndexHtml(html, {
            chunk: { name: 'unknown' },
        });
        expect(result).toBe(html);
    });

    test('returns html unchanged when chunk is undefined', () => {
        const plugin = tdkPlugin({ index: { title: 'T' } });
        const html = '<html></html>';
        const result = plugin.transformIndexHtml(html, {});
        expect(result).toBe(html);
    });
});

// ── Property-Based Tests ──

/**
 * Arbitrary for non-empty printable strings without HTML special chars
 * (to avoid breaking the HTML structure in assertions).
 */
const safeStringArb = fc.stringMatching(/^[a-zA-Z0-9 ,.\-_]{1,60}$/);

describe('PBT: Feature: release-4.0, Property 12: TDK metadata injection invariant', () => {
    test('for any valid TDK object, injected HTML contains the specified title, keywords, and description', () => {
        fc.assert(
            fc.property(
                safeStringArb,
                safeStringArb,
                safeStringArb,
                (title, keywords, description) => {
                    const entryName = 'testpage';
                    const plugin = tdkPlugin({
                        [entryName]: { title, keywords, description },
                    });

                    const html = [
                        '<html><head>',
                        '<title></title>',
                        '<!-- TDK_KEYWORDS -->',
                        '<!-- TDK_DESCRIPTION -->',
                        '</head><body></body></html>',
                    ].join('');

                    const result = plugin.transformIndexHtml(html, {
                        chunk: { name: entryName },
                    });

                    expect(result).toContain(`<title>${title}</title>`);
                    expect(result).toContain(
                        `<meta name="keywords" content="${keywords}">`,
                    );
                    expect(result).toContain(
                        `<meta name="description" content="${description}">`,
                    );
                },
            ),
            { numRuns: 200 },
        );
    });
});
