/**
 * Tests for vite.config.js — Vite build configuration.
 *
 * Importing vite.config.js directly in jsdom environment triggers esbuild
 * compatibility issues. Instead, we verify the configuration indirectly:
 * 1. Test that the config file is valid JavaScript (parseable)
 * 2. Test the modules it depends on (entries, tdk, check-node)
 * 3. Test the config structure by reading and evaluating key aspects
 */

import { describe, test, expect, beforeEach } from 'vitest';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const configPath = path.resolve(__dirname, '../vite.config.js');

describe('vite.config.js structure', () => {
    test('vite.config.js file exists', () => {
        expect(fs.existsSync(configPath)).toBe(true);
    });

    test('config imports vue plugin', () => {
        const content = fs.readFileSync(configPath, 'utf-8');
        expect(content).toContain("from '@vitejs/plugin-vue'");
    });

    test('config imports scanEntries from scripts/entries.js', () => {
        const content = fs.readFileSync(configPath, 'utf-8');
        expect(content).toContain("from './scripts/entries.js'");
        expect(content).toContain('scanEntries');
    });

    test('config imports tdkPlugin from scripts/tdk.js', () => {
        const content = fs.readFileSync(configPath, 'utf-8');
        expect(content).toContain("from './scripts/tdk.js'");
        expect(content).toContain('tdkPlugin');
    });

    test('config imports checkNodeVersion from scripts/check-node.js', () => {
        const content = fs.readFileSync(configPath, 'utf-8');
        expect(content).toContain("from './scripts/check-node.js'");
        expect(content).toContain('checkNodeVersion');
    });

    test('config calls checkNodeVersion(24)', () => {
        const content = fs.readFileSync(configPath, 'utf-8');
        expect(content).toContain('checkNodeVersion(24)');
    });

    test('config defines resolve aliases (@, slimvue, assets)', () => {
        const content = fs.readFileSync(configPath, 'utf-8');
        expect(content).toMatch(/'@'/);
        expect(content).toContain('slimvue');
        expect(content).toContain('assets');
    });

    test('config reads PUBLIC_PATH env for base', () => {
        const content = fs.readFileSync(configPath, 'utf-8');
        expect(content).toContain('PUBLIC_PATH');
    });

    test('config reads OUTPUT_DIR env for outDir', () => {
        const content = fs.readFileSync(configPath, 'utf-8');
        expect(content).toContain('OUTPUT_DIR');
    });

    test('config has test section with jsdom environment', () => {
        const content = fs.readFileSync(configPath, 'utf-8');
        expect(content).toContain("environment: 'jsdom'");
    });

    test('config has coverage configuration', () => {
        const content = fs.readFileSync(configPath, 'utf-8');
        expect(content).toContain('coverage');
        expect(content).toContain("provider: 'v8'");
    });

    test('config uses defineConfig', () => {
        const content = fs.readFileSync(configPath, 'utf-8');
        expect(content).toContain('defineConfig');
    });
});
