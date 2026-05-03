/**
 * Tests for build/entries.js
 */

beforeEach(() => {
    jest.resetModules();
    delete process.env.EXCLUED_ENTRIES;
    delete process.env.BUILD_FILE_TYPE;
});

describe("build/entries.js", () => {
    test("returns an object of page entries", () => {
        const pages = require("../build/entries.js");
        expect(typeof pages).toBe("object");
    });

    test("contains index entry", () => {
        const pages = require("../build/entries.js");
        expect(pages).toHaveProperty("index");
    });

    test("contains subpage-index entry", () => {
        const pages = require("../build/entries.js");
        expect(pages).toHaveProperty("subpage-index");
    });

    test("each entry has entry, template, filename", () => {
        const pages = require("../build/entries.js");
        Object.values(pages).forEach(page => {
            expect(page).toHaveProperty("entry");
            expect(page).toHaveProperty("template");
            expect(page).toHaveProperty("filename");
        });
    });

    test("entry paths end with .js", () => {
        const pages = require("../build/entries.js");
        Object.values(pages).forEach(page => {
            expect(page.entry).toMatch(/\.js$/);
        });
    });

    test("filename uses twig extension by default", () => {
        const pages = require("../build/entries.js");
        Object.values(pages).forEach(page => {
            expect(page.filename).toMatch(/\.twig$/);
        });
    });

    test("filename uses html extension when BUILD_FILE_TYPE=html", () => {
        process.env.BUILD_FILE_TYPE = "html";
        const pages = require("../build/entries.js");
        Object.values(pages).forEach(page => {
            expect(page.filename).toMatch(/\.html$/);
        });
    });

    test("template path includes buildFileType", () => {
        const pages = require("../build/entries.js");
        Object.values(pages).forEach(page => {
            expect(page.template).toMatch(/index\.twig$/);
        });
    });

    test("excluded entries are filtered out", () => {
        // Exclude the subpage entry
        process.env.EXCLUED_ENTRIES = "subpage";
        const pages = require("../build/entries.js");
        expect(pages).not.toHaveProperty("subpage-index");
        expect(pages).toHaveProperty("index");
    });

    test("entries include tdk metadata when available", () => {
        const pages = require("../build/entries.js");
        // index should have tdk data merged in
        expect(pages.index).toHaveProperty("title");
        expect(pages.index).toHaveProperty("keywords");
    });
});
