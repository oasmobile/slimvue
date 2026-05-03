/**
 * TDK (Title, Description, Keywords) metadata injection Vite plugin.
 *
 * Uses Vite's `transformIndexHtml` hook to inject page-specific metadata
 * into the HTML output during build.
 */

/**
 * Default TDK metadata map for template pages.
 */
export const defaultTdkMap = {
    index: {
        title: 'slimvue home page',
        keywords: 'slimvue,vue,vite',
        description:
            'A front-end framework based on vue.js, and is to be used together with slimapp',
    },
    'subpage-index': {
        title: 'slimvue subpage',
        keywords: 'slimvue,vue,vite',
        description: 'the description that show what this page is',
    },
};

/**
 * Create a Vite plugin that injects TDK metadata into HTML pages.
 *
 * @param {object} [tdkMap] - Map of entry name to TDK metadata object.
 *   Each value should have `title`, `keywords`, and `description` string fields.
 * @returns {import('vite').Plugin} Vite plugin
 */
export function tdkPlugin(tdkMap = defaultTdkMap) {
    return {
        name: 'slimvue-tdk',
        transformIndexHtml(html, ctx) {
            const entry = ctx.chunk?.name;
            const tdk = tdkMap[entry];
            if (!tdk) return html;

            let result = html;

            if (tdk.title) {
                result = result.replace(
                    /<title>.*?<\/title>/,
                    `<title>${tdk.title}</title>`,
                );
            }
            if (tdk.keywords) {
                result = result.replace(
                    /<!-- TDK_KEYWORDS -->/,
                    `<meta name="keywords" content="${tdk.keywords}">`,
                );
            }
            if (tdk.description) {
                result = result.replace(
                    /<!-- TDK_DESCRIPTION -->/,
                    `<meta name="description" content="${tdk.description}">`,
                );
            }

            return result;
        },
    };
}
