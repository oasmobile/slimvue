/**
 * Tests for build/webpack-modify/*.js
 *
 * These modules operate on webpack-chain Config objects.
 * We create minimal mock/stub objects to test the logic.
 */

describe("build/webpack-modify/plugin.js", () => {
    const modifyPlugin = require("../build/webpack-modify/plugin.js");

    test("is a function", () => {
        expect(typeof modifyPlugin).toBe("function");
    });

    test("does nothing when config has no copy plugin", () => {
        const config = {
            plugins: { has: jest.fn().mockReturnValue(false) }
        };
        // Should not throw
        expect(() => modifyPlugin(config)).not.toThrow();
        expect(config.plugins.has).toHaveBeenCalledWith("copy");
    });

    test("modifies copy plugin to point to static dir", () => {
        let tapCallback;
        const config = {
            plugins: { has: jest.fn().mockReturnValue(true) },
            plugin: jest.fn().mockReturnValue({
                tap: jest.fn(fn => {
                    tapCallback = fn;
                })
            })
        };
        modifyPlugin(config);
        expect(config.plugin).toHaveBeenCalledWith("copy");

        // Simulate tap callback with copy plugin args
        const result = tapCallback([[{ from: "public", to: "/old/path" }]]);
        expect(result[0][0].to).toContain("static");
    });

    test("tap returns args unchanged when empty", () => {
        let tapCallback;
        const config = {
            plugins: { has: jest.fn().mockReturnValue(true) },
            plugin: jest.fn().mockReturnValue({
                tap: jest.fn(fn => {
                    tapCallback = fn;
                })
            })
        };
        modifyPlugin(config);
        const result = tapCallback([]);
        expect(result).toEqual([]);
    });
});

describe("build/webpack-modify/module.js", () => {
    const modifyModule = require("../build/webpack-modify/module.js");

    test("is a function", () => {
        expect(typeof modifyModule).toBe("function");
    });

    test("taps vue-loader options to set transformAssetUrls", () => {
        let tapCallback;
        const config = {
            module: {
                rule: jest.fn().mockReturnValue({
                    use: jest.fn().mockReturnValue({
                        tap: jest.fn(fn => {
                            tapCallback = fn;
                        })
                    })
                })
            }
        };
        modifyModule(config);
        expect(config.module.rule).toHaveBeenCalledWith("vue");

        // Simulate tap callback
        const options = { someOption: true };
        const result = tapCallback(options);
        expect(result).toHaveProperty("transformAssetUrls");
        expect(result.someOption).toBe(true);
    });
});

describe("build/webpack-modify/index.js", () => {
    const modify = require("../build/webpack-modify/index.js");

    test("is a function", () => {
        expect(typeof modify).toBe("function");
    });

    test("calls merge, modifyPlugin, and modifyModule on config", () => {
        let tapCallbacks = {};
        const config = {
            merge: jest.fn(),
            plugins: { has: jest.fn().mockReturnValue(false) },
            module: {
                rule: jest.fn().mockReturnValue({
                    use: jest.fn().mockReturnValue({
                        tap: jest.fn(fn => {
                            tapCallbacks.module = fn;
                        })
                    })
                })
            }
        };
        const result = modify(config);
        expect(config.merge).toHaveBeenCalled();
        expect(result).toBe(config);
    });
});
