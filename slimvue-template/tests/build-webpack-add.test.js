/**
 * Tests for build/webpack-add/index.js
 */

describe("build/webpack-add/index.js", () => {
    const webpackAdd = require("../build/webpack-add/index.js");

    test("exports resolve config", () => {
        expect(webpackAdd).toHaveProperty("resolve");
        expect(webpackAdd.resolve).toHaveProperty("alias");
    });

    test("exports plugin config", () => {
        expect(webpackAdd).toHaveProperty("plugin");
        expect(webpackAdd.plugin).toHaveProperty("banner-plugin");
    });
});
