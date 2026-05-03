/**
 * Tests for build/tdk.js
 */

describe("build/tdk.js", () => {
    const tdk = require("../build/tdk.js");

    test("exports an object", () => {
        expect(typeof tdk).toBe("object");
    });

    test("has index page entry", () => {
        expect(tdk).toHaveProperty("index");
        expect(tdk.index).toHaveProperty("title");
        expect(tdk.index).toHaveProperty("keywords");
        expect(tdk.index).toHaveProperty("description");
    });

    test("has subpage-index entry", () => {
        expect(tdk).toHaveProperty("subpage-index");
        expect(tdk["subpage-index"]).toHaveProperty("title");
    });

    test("index title is a non-empty string", () => {
        expect(typeof tdk.index.title).toBe("string");
        expect(tdk.index.title.length).toBeGreaterThan(0);
    });
});
