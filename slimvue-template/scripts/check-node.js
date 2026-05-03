/**
 * Node.js version check utility.
 *
 * Supports two usage modes:
 * - Standalone script: `node scripts/check-node.js`
 * - Module import: `import { checkNodeVersion } from './scripts/check-node.js'`
 */

/**
 * Check that the current Node.js version meets the minimum requirement.
 * Exits the process with code 1 if the constraint is not met.
 *
 * @param {number} minMajor - Minimum required major version
 */
export function checkNodeVersion(minMajor) {
    const current = parseInt(process.version.slice(1), 10);
    if (current < minMajor) {
        console.error(
            `Error: Node.js >= ${minMajor} is required. Current version: ${process.version}`,
        );
        process.exit(1);
    }
}

// Standalone execution: check for Node.js >= 24
checkNodeVersion(24);
