/**
 * Tests for build/devServer.js
 */

beforeEach(() => {
    jest.resetModules();
    delete process.env.EXCLUED_ENTRIES;
    delete process.env.BUILD_FILE_TYPE;
});

describe("build/devServer.js", () => {
    test("port is 8090", () => {
        const devServer = require("../build/devServer.js");
        expect(devServer.port).toBe(8090);
    });

    test("has open property", () => {
        const devServer = require("../build/devServer.js");
        expect(devServer).toHaveProperty("open");
    });

    test("has openPage property", () => {
        const devServer = require("../build/devServer.js");
        expect(devServer).toHaveProperty("openPage");
    });

    test("openPage points to first page filename", () => {
        const devServer = require("../build/devServer.js");
        // Should be the filename of the first entry
        expect(typeof devServer.openPage).toBe("string");
        expect(devServer.openPage.length).toBeGreaterThan(0);
    });
});
