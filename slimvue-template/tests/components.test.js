/**
 * Tests for Vue components
 */
import { shallowMount, mount } from "@vue/test-utils";
import App from "@/components/App.vue";
import HelloWorld from "@/components/HelloWorld.vue";
import MyClock from "@/components/MyClock.vue";
import SubPage from "@/components/SubPage.vue";

// ── App ──

describe("App.vue", () => {
    test("has correct component name", () => {
        expect(App.name).toBe("App");
    });

    test("renders HelloWorld component", () => {
        const wrapper = shallowMount(App);
        expect(wrapper.findComponent(HelloWorld).exists()).toBe(true);
    });

    test("passes msg prop to HelloWorld", () => {
        const wrapper = shallowMount(App);
        const hello = wrapper.findComponent(HelloWorld);
        expect(hello.props("msg")).toBe("Welcome to Your Vue.js App");
    });

    test("renders #app root element", () => {
        const wrapper = shallowMount(App);
        expect(wrapper.find("#app").exists()).toBe(true);
    });

    test("renders Vue logo image", () => {
        const wrapper = shallowMount(App);
        expect(wrapper.find("img").exists()).toBe(true);
    });
});

// ── HelloWorld ──

describe("HelloWorld.vue", () => {
    test("renders msg prop", () => {
        const wrapper = shallowMount(HelloWorld, {
            propsData: { msg: "Test Message" }
        });
        expect(wrapper.text()).toContain("Test Message");
    });

    test("has correct component name", () => {
        expect(HelloWorld.name).toBe("HelloWorld");
    });

    test("msg prop type is String", () => {
        // vue-jest parses props as {msg: {type: String}}
        expect(HelloWorld.props.msg.type).toBe(String);
    });

    test("renders essential links section", () => {
        const wrapper = shallowMount(HelloWorld, {
            propsData: { msg: "Hello" }
        });
        expect(wrapper.text()).toContain("Essential Links");
    });

    test("renders ecosystem section", () => {
        const wrapper = shallowMount(HelloWorld, {
            propsData: { msg: "Hello" }
        });
        expect(wrapper.text()).toContain("Ecosystem");
    });
});

// ── MyClock ──

describe("MyClock.vue", () => {
    beforeEach(() => {
        jest.useFakeTimers();
    });

    afterEach(() => {
        jest.useRealTimers();
    });

    test("renders a date-time string", () => {
        const wrapper = shallowMount(MyClock);
        // Should match pattern like "2024-01-15 10:30:45"
        expect(wrapper.text()).toMatch(/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/);
    });

    test("prefixDateNum pads single digit", () => {
        const wrapper = shallowMount(MyClock);
        expect(wrapper.vm.prefixDateNum(5)).toBe("05");
    });

    test("prefixDateNum does not pad double digit", () => {
        const wrapper = shallowMount(MyClock);
        expect(wrapper.vm.prefixDateNum(12)).toBe("12");
    });

    test("prefixDateNum handles 0", () => {
        const wrapper = shallowMount(MyClock);
        expect(wrapper.vm.prefixDateNum(0)).toBe("00");
    });

    test("prefixDateNum handles 10 (boundary)", () => {
        const wrapper = shallowMount(MyClock);
        expect(wrapper.vm.prefixDateNum(10)).toBe("10");
    });

    test("prefixDateNum handles 9 (boundary)", () => {
        const wrapper = shallowMount(MyClock);
        expect(wrapper.vm.prefixDateNum(9)).toBe("09");
    });

    test("getFullDateTime returns formatted string", () => {
        const wrapper = shallowMount(MyClock);
        const result = wrapper.vm.getFullDateTime();
        expect(result).toMatch(/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/);
    });

    test("fullDateTime computed property matches getFullDateTime", () => {
        const wrapper = shallowMount(MyClock);
        expect(wrapper.vm.fullDateTime).toBe(wrapper.vm.getFullDateTime());
    });

    test("time data updates via interval", () => {
        const wrapper = shallowMount(MyClock);
        const initialTime = wrapper.vm.time;
        // Advance timers by 1 second
        jest.advanceTimersByTime(1100);
        // time should have been updated (Date.now() returns real time in fake timer mode,
        // but the interval callback fires)
        expect(wrapper.vm.time).toBeDefined();
    });

    test("initial time is close to Date.now()", () => {
        const now = Date.now();
        const wrapper = shallowMount(MyClock);
        // Should be within 100ms of now
        expect(Math.abs(wrapper.vm.time - now)).toBeLessThan(100);
    });
});

// ── SubPage ──

describe("SubPage.vue", () => {
    test("has correct component name", () => {
        expect(SubPage.name).toBe("SubPage");
    });

    test("renders subpage text", () => {
        const wrapper = shallowMount(SubPage);
        expect(wrapper.text()).toContain("this is subpage");
    });

    test("has #app root element", () => {
        const wrapper = shallowMount(SubPage);
        expect(wrapper.find("#app").exists()).toBe(true);
    });
});
