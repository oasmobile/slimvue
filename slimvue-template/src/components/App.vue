<template>
    <div id="app">
        <img alt="Vue logo" src="@/assets/logo.png" />
        <HelloWorld msg="Welcome to Your Vue.js App" @greet="onGreet" />
        <MyClock />
        <div class="logo"></div>
        <div v-if="bridge" class="bridge-info">
            <p>Bridge data: {{ JSON.stringify(bridge) }}</p>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import slimvue from 'slimvue';
import HelloWorld from './HelloWorld.vue';
import MyClock from './MyClock.vue';

const bridge = computed(() => {
    try {
        return slimvue.bridge;
    } catch {
        return null;
    }
});

function onGreet(name) {
    slimvue.log('Greeting from HelloWorld:', name);
}
</script>

<style lang="scss">
#app {
    font-family: Avenir, Helvetica, Arial, sans-serif;
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
    text-align: center;
    color: #2c3e50;
    margin-top: 60px;
    .logo {
        width: 200px;
        height: 200px;
        margin: 0 auto;
        background: url('@/assets/logo.png') no-repeat;
    }
    .bridge-info {
        margin-top: 20px;
        font-size: 14px;
        color: #666;
    }
}
</style>
