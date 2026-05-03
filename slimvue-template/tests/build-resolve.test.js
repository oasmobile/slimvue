/**
 * Tests for build/webpack-add/resolve.js
 */

describe("build/webpack-add/resolve.js", () => {
    const resolve = require("../build/webpack-add/resolve.js");

    test("exports an object with alias key", () => {
        expect(resolve).toHaveProperty("alias");
    });

    test("alias.slimvue points to slimvue.js", () => {
        expect(resolve.alias.slimvue).toMatch(/slimvue\.js$/);
    });

    test("alias.assets points to src/assets", () => {
        expect(resolve.alias.assets).toMatch(/src\/assets$/);
    });
});
