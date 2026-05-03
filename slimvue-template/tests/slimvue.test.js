/**
 * Tests for slimvue.js — the core front-end module.
 *
 * slimvue.js does two things on import:
 *   1. Sets Vue.config.productionTip based on NODE_ENV
 *   2. Adds $toCssUrl / $toCssBackgroundImage to Vue.prototype
 *
 * The default export is an object with:
 *   - log level constants & getter/setter
 *   - isDebug getter
 *   - bridge getter
 *   - mount()
 *   - log/debug/info/warn/error helpers
 */

let slimvue;

beforeEach(() => {
    // Reset module cache so each test gets a fresh import
    jest.resetModules();
    // Default to non-production
    process.env.NODE_ENV = "development";
    process.env.VUE_APP_BRIDGE = JSON.stringify({ test: true });
});

function loadSlimvue() {
    slimvue = require("../slimvue.js").default;
    return slimvue;
}

// ── Log level constants ──

describe("log level constants", () => {
    test("DEBUG_LOG_LEVEL is 0", () => {
        loadSlimvue();
        expect(slimvue.DEBUG_LOG_LEVEL).toBe(0);
    });

    test("DEFAULT_LOG_LEVEL is 10", () => {
        loadSlimvue();
        expect(slimvue.DEFAULT_LOG_LEVEL).toBe(10);
    });

    test("INFO_LOG_LEVEL is 20", () => {
        loadSlimvue();
        expect(slimvue.INFO_LOG_LEVEL).toBe(20);
    });

    test("WARNING_LOG_LEVEL is 30", () => {
        loadSlimvue();
        expect(slimvue.WARNING_LOG_LEVEL).toBe(30);
    });

    test("ERROR_LOG_LEVEL is 40", () => {
        loadSlimvue();
        expect(slimvue.ERROR_LOG_LEVEL).toBe(40);
    });
});

// ── isDebug ──

describe("isDebug", () => {
    test("returns true in development", () => {
        process.env.NODE_ENV = "development";
        loadSlimvue();
        expect(slimvue.isDebug).toBe(true);
    });

    test("returns false in production", () => {
        process.env.NODE_ENV = "production";
        loadSlimvue();
        expect(slimvue.isDebug).toBe(false);
    });
});

// ── logLevel getter/setter ──

describe("logLevel", () => {
    test("defaults to DEBUG_LOG_LEVEL in development", () => {
        process.env.NODE_ENV = "development";
        loadSlimvue();
        expect(slimvue.logLevel).toBe(slimvue.DEBUG_LOG_LEVEL);
    });

    test("defaults to INFO_LOG_LEVEL in production", () => {
        process.env.NODE_ENV = "production";
        loadSlimvue();
        expect(slimvue.logLevel).toBe(slimvue.INFO_LOG_LEVEL);
    });

    test("can be set to a custom value", () => {
        loadSlimvue();
        slimvue.logLevel = 25;
        expect(slimvue.logLevel).toBe(25);
    });

    test("setting to 0 uses custom value, not default", () => {
        loadSlimvue();
        slimvue.logLevel = 0;
        expect(slimvue.logLevel).toBe(0);
    });
});

// ── bridge ──

describe("bridge", () => {
    test("reads from VUE_APP_BRIDGE env when window.bridge is undefined", () => {
        delete window.bridge;
        process.env.VUE_APP_BRIDGE = JSON.stringify({ user: "alice" });
        loadSlimvue();
        expect(slimvue.bridge).toEqual({ user: "alice" });
    });

    test("reads from window.bridge when defined", () => {
        window.bridge = { user: "bob" };
        loadSlimvue();
        expect(slimvue.bridge).toEqual({ user: "bob" });
        delete window.bridge;
    });
});

// ── mount ──

describe("mount", () => {
    test("creates a Vue instance and assigns to window.slimvue", () => {
        loadSlimvue();
        // Create a component with render function (runtime-only build has no template compiler)
        const component = {
            render(h) {
                return h("div", "test");
            }
        };
        document.body.innerHTML = '<div id="app"></div>';
        const app = slimvue.mount(component);
        expect(app).toBeDefined();
        expect(window.slimvue).toBe(app);
        // cleanup
        delete window.slimvue;
    });
});

// ── logging methods ──

describe("logging methods", () => {
    let consoleSpy;

    beforeEach(() => {
        loadSlimvue();
        consoleSpy = {
            log: jest.spyOn(console, "log").mockImplementation(),
            debug: jest.spyOn(console, "debug").mockImplementation(),
            info: jest.spyOn(console, "info").mockImplementation(),
            warn: jest.spyOn(console, "warn").mockImplementation(),
            error: jest.spyOn(console, "error").mockImplementation()
        };
    });

    afterEach(() => {
        Object.values(consoleSpy).forEach(spy => spy.mockRestore());
    });

    test("log() outputs when logLevel <= DEFAULT_LOG_LEVEL", () => {
        slimvue.logLevel = 0;
        slimvue.log("hello");
        expect(consoleSpy.log).toHaveBeenCalledWith("hello");
    });

    test("log() is silent when logLevel > DEFAULT_LOG_LEVEL", () => {
        slimvue.logLevel = 20;
        slimvue.log("hello");
        expect(consoleSpy.log).not.toHaveBeenCalled();
    });

    test("debug() outputs when logLevel <= DEBUG_LOG_LEVEL", () => {
        slimvue.logLevel = 0;
        slimvue.debug("dbg");
        expect(consoleSpy.debug).toHaveBeenCalledWith("dbg");
    });

    test("debug() is silent when logLevel > DEBUG_LOG_LEVEL", () => {
        slimvue.logLevel = 1;
        slimvue.debug("dbg");
        expect(consoleSpy.debug).not.toHaveBeenCalled();
    });

    test("info() outputs when logLevel <= INFO_LOG_LEVEL", () => {
        slimvue.logLevel = 20;
        slimvue.info("inf");
        expect(consoleSpy.info).toHaveBeenCalledWith("inf");
    });

    test("info() is silent when logLevel > INFO_LOG_LEVEL", () => {
        slimvue.logLevel = 21;
        slimvue.info("inf");
        expect(consoleSpy.info).not.toHaveBeenCalled();
    });

    test("warn() outputs when logLevel <= WARNING_LOG_LEVEL", () => {
        slimvue.logLevel = 30;
        slimvue.warn("wrn");
        expect(consoleSpy.warn).toHaveBeenCalledWith("wrn");
    });

    test("warn() is silent when logLevel > WARNING_LOG_LEVEL", () => {
        slimvue.logLevel = 31;
        slimvue.warn("wrn");
        expect(consoleSpy.warn).not.toHaveBeenCalled();
    });

    test("error() outputs when logLevel <= ERROR_LOG_LEVEL", () => {
        slimvue.logLevel = 40;
        slimvue.error("err");
        expect(consoleSpy.error).toHaveBeenCalledWith("err");
    });

    test("error() is silent when logLevel > ERROR_LOG_LEVEL", () => {
        slimvue.logLevel = 41;
        slimvue.error("err");
        expect(consoleSpy.error).not.toHaveBeenCalled();
    });

    test("log methods accept multiple arguments", () => {
        slimvue.logLevel = 0;
        slimvue.log("a", "b", "c");
        expect(consoleSpy.log).toHaveBeenCalledWith("a", "b", "c");
    });
});

// ── Vue prototype extensions ──

describe("Vue prototype extensions", () => {
    test("$toCssUrl wraps url in url()", () => {
        // Must load slimvue first to register prototype methods
        loadSlimvue();
        const Vue = require("vue");
        expect(new Vue().$toCssUrl("img.png")).toBe("url(img.png)");
    });

    test("$toCssBackgroundImage returns backgroundImage style object", () => {
        loadSlimvue();
        const Vue = require("vue");
        const result = new Vue().$toCssBackgroundImage("img.png");
        expect(result).toEqual({ backgroundImage: "url(img.png)" });
    });
});
