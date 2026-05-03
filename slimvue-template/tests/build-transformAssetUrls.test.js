/**
 * Tests for build/transformAssetUrls.js
 */

describe("build/transformAssetUrls.js", () => {
    test("exports an object", () => {
        const urls = require("../build/transformAssetUrls.js");
        expect(typeof urls).toBe("object");
    });

    test("is an empty object by default", () => {
        const urls = require("../build/transformAssetUrls.js");
        expect(Object.keys(urls)).toHaveLength(0);
    });
});
