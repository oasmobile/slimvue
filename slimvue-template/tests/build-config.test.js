/**
 * Tests for build/config.js
 */

beforeEach(() => {
    jest.resetModules();
    // Clear relevant env vars
    delete process.env.EXCLUED_ENTRIES;
    delete process.env.PUBLIC_PATH;
    delete process.env.OUTPUT_DIR;
    delete process.env.ASSETS_DIR;
    delete process.env.BUILD_FILE_TYPE;
});

describe("build/config.js", () => {
    test("default publicPath is /", () => {
        const config = require("../build/config.js");
        expect(config.publicPath).toBe("/");
    });

    test("publicPath reads from PUBLIC_PATH env", () => {
        process.env.PUBLIC_PATH = "/cdn/";
        const config = require("../build/config.js");
        expect(config.publicPath).toBe("/cdn/");
    });

    test("default outputDir is dist", () => {
        const config = require("../build/config.js");
        expect(config.outputDir).toBe("dist");
    });

    test("outputDir reads from OUTPUT_DIR env", () => {
        process.env.OUTPUT_DIR = "build-output";
        const config = require("../build/config.js");
        expect(config.outputDir).toBe("build-output");
    });

    test("default assetsDir is undefined", () => {
        const config = require("../build/config.js");
        expect(config.assetsDir).toBeUndefined();
    });

    test("assetsDir reads from ASSETS_DIR env", () => {
        process.env.ASSETS_DIR = "static";
        const config = require("../build/config.js");
        expect(config.assetsDir).toBe("static");
    });

    test("default buildFileType is twig", () => {
        const config = require("../build/config.js");
        expect(config.buildFileType).toBe("twig");
    });

    test("buildFileType reads from BUILD_FILE_TYPE env", () => {
        process.env.BUILD_FILE_TYPE = "html";
        const config = require("../build/config.js");
        expect(config.buildFileType).toBe("html");
    });

    test("exculedEntries defaults to empty array", () => {
        const config = require("../build/config.js");
        expect(config.exculedEntries).toEqual([]);
    });

    test("exculedEntries parses comma-separated env", () => {
        process.env.EXCLUED_ENTRIES = "admin,debug";
        const config = require("../build/config.js");
        expect(config.exculedEntries).toHaveLength(2);
        // Each entry should be a full path joined with entryDirectory
        config.exculedEntries.forEach(entry => {
            expect(entry).toContain("src/entries");
        });
    });

    test("entryDirectory points to src/entries", () => {
        const config = require("../build/config.js");
        expect(config.entryDirectory).toMatch(/src\/entries$/);
    });

    test("templateDirectory points to template", () => {
        const config = require("../build/config.js");
        expect(config.templateDirectory).toMatch(/template$/);
    });
});
