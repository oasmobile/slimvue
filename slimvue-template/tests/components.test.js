/**
 * Tests for Vue components.
 *
 * Uses @vue/test-utils ^2 (Vue 3 compatible) with Vitest.
 * Components are currently Vue 2 Options API — these tests will be
 * updated in Task 7 when components are migrated to Vue 3 <script setup>.
 */

import { describe, test, expect, beforeEach, afterEach, vi } from 'vitest';
import { shallowMount, mount } from '@vue/test-utils';
import App from '@/components/App.vue';
import HelloWorld from '@/components/HelloWorld.vue';
import MyClock from '@/components/MyClock.vue';
import SubPage from '@/components/SubPage.vue';

// ── App ──

describe('App.vue', () => {
    test('renders HelloWorld component', () => {
        const wrapper = shallowMount(App);
        expect(wrapper.findComponent(HelloWorld).exists()).toBe(true);
    });

    test('passes msg prop to HelloWorld', () => {
        const wrapper = shallowMount(App);
        const hello = wrapper.findComponent(HelloWorld);
        expect(hello.props('msg')).toBe('Welcome to Your Vue.js App');
    });

    test('renders #app root element', () => {
        const wrapper = shallowMount(App);
        expect(wrapper.find('#app').exists()).toBe(true);
    });

    test('renders Vue logo image', () => {
        const wrapper = shallowMount(App);
        expect(wrapper.find('img').exists()).toBe(true);
    });
});

// ── HelloWorld ──

describe('HelloWorld.vue', () => {
    test('renders msg prop', () => {
        const wrapper = shallowMount(HelloWorld, {
            props: { msg: 'Test Message' },
        });
        expect(wrapper.text()).toContain('Test Message');
    });

    test('renders essential links section', () => {
        const wrapper = shallowMount(HelloWorld, {
            props: { msg: 'Hello' },
        });
        expect(wrapper.text()).toContain('Essential Links');
    });

    test('renders ecosystem section', () => {
        const wrapper = shallowMount(HelloWorld, {
            props: { msg: 'Hello' },
        });
        expect(wrapper.text()).toContain('Ecosystem');
    });
});

// ── MyClock ──

describe('MyClock.vue', () => {
    beforeEach(() => {
        vi.useFakeTimers();
    });

    afterEach(() => {
        vi.useRealTimers();
    });

    test('renders a date-time string', () => {
        const wrapper = shallowMount(MyClock);
        // Should match pattern like "2024-01-15 10:30:45"
        expect(wrapper.text()).toMatch(/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/);
    });

    test('prefixDateNum pads single digit', () => {
        const wrapper = shallowMount(MyClock);
        expect(wrapper.vm.prefixDateNum(5)).toBe('05');
    });

    test('prefixDateNum does not pad double digit', () => {
        const wrapper = shallowMount(MyClock);
        expect(wrapper.vm.prefixDateNum(12)).toBe('12');
    });

    test('prefixDateNum handles 0', () => {
        const wrapper = shallowMount(MyClock);
        expect(wrapper.vm.prefixDateNum(0)).toBe('00');
    });

    test('prefixDateNum handles 10 (boundary)', () => {
        const wrapper = shallowMount(MyClock);
        expect(wrapper.vm.prefixDateNum(10)).toBe('10');
    });

    test('prefixDateNum handles 9 (boundary)', () => {
        const wrapper = shallowMount(MyClock);
        expect(wrapper.vm.prefixDateNum(9)).toBe('09');
    });

    test('getFullDateTime returns formatted string', () => {
        const wrapper = shallowMount(MyClock);
        const result = wrapper.vm.getFullDateTime();
        expect(result).toMatch(/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/);
    });

    test('fullDateTime computed property matches getFullDateTime', () => {
        const wrapper = shallowMount(MyClock);
        expect(wrapper.vm.fullDateTime).toBe(wrapper.vm.getFullDateTime());
    });

    test('initial time is close to Date.now()', () => {
        const now = Date.now();
        const wrapper = shallowMount(MyClock);
        expect(Math.abs(wrapper.vm.time - now)).toBeLessThan(100);
    });
});

// ── SubPage ──

describe('SubPage.vue', () => {
    test('renders subpage text', () => {
        const wrapper = shallowMount(SubPage);
        expect(wrapper.text()).toContain('this is subpage');
    });

    test('has #app root element', () => {
        const wrapper = shallowMount(SubPage);
        expect(wrapper.find('#app').exists()).toBe(true);
    });
});
