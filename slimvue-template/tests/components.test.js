/**
 * Tests for Vue 3 components using <script setup> and Composition API.
 *
 * Uses @vue/test-utils ^2 (Vue 3 compatible) with Vitest.
 * Components use <script setup> — internal state is not exposed on wrapper.vm.
 * Composable logic (useClock) is tested via direct import.
 */

import { describe, test, expect, beforeEach, afterEach, vi } from 'vitest';
import { shallowMount, mount } from '@vue/test-utils';
import App from '@/components/App.vue';
import HelloWorld from '@/components/HelloWorld.vue';
import MyClock from '@/components/MyClock.vue';
import SubPage from '@/components/SubPage.vue';
import {
    prefixDateNum,
    formatDateTime,
} from '@/composables/useClock';

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

    test('renders MyClock component', () => {
        const wrapper = shallowMount(App);
        expect(wrapper.findComponent(MyClock).exists()).toBe(true);
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

    test('renders Vite documentation link', () => {
        const wrapper = shallowMount(HelloWorld, {
            props: { msg: 'Hello' },
        });
        expect(wrapper.text()).toContain('Vite documentation');
    });

    test('emits greet event when button is clicked', async () => {
        const wrapper = mount(HelloWorld, {
            props: { msg: 'Hello' },
        });
        await wrapper.find('button').trigger('click');
        expect(wrapper.emitted('greet')).toBeTruthy();
        expect(wrapper.emitted('greet')[0]).toEqual(['World']);
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
        expect(wrapper.text()).toMatch(/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/);
    });
});

// ── useClock composable ──

describe('useClock composable', () => {
    describe('prefixDateNum', () => {
        test('pads single digit', () => {
            expect(prefixDateNum(5)).toBe('05');
        });

        test('does not pad double digit', () => {
            expect(prefixDateNum(12)).toBe('12');
        });

        test('handles 0', () => {
            expect(prefixDateNum(0)).toBe('00');
        });

        test('handles 10 (boundary)', () => {
            expect(prefixDateNum(10)).toBe('10');
        });

        test('handles 9 (boundary)', () => {
            expect(prefixDateNum(9)).toBe('09');
        });
    });

    describe('formatDateTime', () => {
        test('returns formatted string', () => {
            const result = formatDateTime(new Date());
            expect(result).toMatch(/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/);
        });

        test('formats a known date correctly', () => {
            const date = new Date(2025, 0, 5, 8, 3, 7); // Jan 5, 2025 08:03:07
            expect(formatDateTime(date)).toBe('2025-01-05 08:03:07');
        });

        test('formats midnight correctly', () => {
            const date = new Date(2025, 11, 31, 0, 0, 0); // Dec 31, 2025 00:00:00
            expect(formatDateTime(date)).toBe('2025-12-31 00:00:00');
        });
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
