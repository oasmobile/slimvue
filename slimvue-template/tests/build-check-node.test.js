/**
 * Tests for scripts/check-node.js — Node.js version check utility.
 */

import { describe, test, expect, vi, afterEach } from 'vitest';
import { checkNodeVersion } from '../scripts/check-node.js';

describe('checkNodeVersion()', () => {
    afterEach(() => {
        vi.restoreAllMocks();
    });

    test('does not exit when current version meets minimum', () => {
        const exitSpy = vi
            .spyOn(process, 'exit')
            .mockImplementation(() => {});
        // Current Node.js version is >= 24, so checking for 1 should pass
        checkNodeVersion(1);
        expect(exitSpy).not.toHaveBeenCalled();
    });

    test('exits with code 1 when current version is below minimum', () => {
        const exitSpy = vi
            .spyOn(process, 'exit')
            .mockImplementation(() => {});
        const errorSpy = vi
            .spyOn(console, 'error')
            .mockImplementation(() => {});

        // Require a version far above any current Node.js
        checkNodeVersion(999);

        expect(exitSpy).toHaveBeenCalledWith(1);
        expect(errorSpy).toHaveBeenCalledWith(
            expect.stringContaining('Node.js >= 999 is required'),
        );
    });

    test('outputs current version in error message', () => {
        vi.spyOn(process, 'exit').mockImplementation(() => {});
        const errorSpy = vi
            .spyOn(console, 'error')
            .mockImplementation(() => {});

        checkNodeVersion(999);

        expect(errorSpy).toHaveBeenCalledWith(
            expect.stringContaining(process.version),
        );
    });
});
