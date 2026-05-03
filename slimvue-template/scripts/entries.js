import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));

/**
 * Recursively scan entry directory for .js files and build page configurations.
 *
 * @param {string} directoryPath - Directory to scan
 * @param {object} options - Scan options
 * @param {string} options.entryDir - Root entry directory (for relative path calculation)
 * @param {string} options.templateDir - Template directory
 * @param {string} options.buildFileType - Output file type ('twig' or 'html')
 * @param {string[]} options.excludedEntries - Absolute paths of entries to exclude
 * @param {object} options.tdkMap - TDK metadata map keyed by entry name
 * @returns {object} Pages configuration map
 */
function getPages(directoryPath, options) {
    const { entryDir, templateDir, buildFileType, excludedEntries, tdkMap } =
        options;

    let items;
    try {
        items = fs.readdirSync(directoryPath);
    } catch {
        return {};
    }

    const pages = {};

    for (const item of items) {
        const itemPath = path.join(directoryPath, item);

        if (excludedEntries.includes(itemPath)) {
            continue;
        }

        const stat = fs.statSync(itemPath);

        if (stat.isDirectory()) {
            Object.assign(pages, getPages(itemPath, options));
        } else if (stat.isFile() && /\.js$/.test(item)) {
            const relativePath = path.relative(entryDir, itemPath);
            const key = relativePath
                .replace('.js', '')
                .replace(/(\/|\\)/g, '-');

            pages[key] = {
                entry: itemPath,
                template: path.join(templateDir, `index.${buildFileType}`),
                filename:
                    'pages/' + relativePath.replace(/js$/, buildFileType),
                ...tdkMap[key],
            };
        }
    }

    return pages;
}

/**
 * Scan multi-page entries from src/entries/ directory.
 *
 * @param {object} [options] - Override options
 * @param {string} [options.entryDir] - Entry directory path
 * @param {string} [options.templateDir] - Template directory path
 * @param {string} [options.buildFileType] - Output file type
 * @param {string[]} [options.excludedEntries] - Entries to exclude (relative to entryDir)
 * @param {object} [options.tdkMap] - TDK metadata map
 * @returns {object} Object with `pages` (full config) and `inputMap` (Rollup input map)
 */
export function scanEntries(options = {}) {
    const {
        entryDir = path.resolve(__dirname, '../src/entries'),
        templateDir = path.resolve(__dirname, '../template'),
        buildFileType = process.env.BUILD_FILE_TYPE || 'twig',
        excludedEntries: rawExcluded = (
            process.env.EXCLUED_ENTRIES || ''
        )
            .split(',')
            .filter(Boolean),
        tdkMap = {},
    } = options;

    // Convert relative excluded entries to absolute paths
    const excludedEntries = rawExcluded.map((item) =>
        path.join(entryDir, item),
    );

    const pages = getPages(entryDir, {
        entryDir,
        templateDir,
        buildFileType,
        excludedEntries,
        tdkMap,
    });

    // Build Rollup input map for Vite
    const inputMap = {};
    for (const [key, config] of Object.entries(pages)) {
        inputMap[key] = config.entry;
    }

    return { pages, inputMap };
}
