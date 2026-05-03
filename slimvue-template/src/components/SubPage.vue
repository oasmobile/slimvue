<template>
    <div id="app">
        <div class="logo"></div>
        <div class="text">{{ state.title }}</div>
    </div>
</template>

<script setup>
import { reactive } from 'vue';

const state = reactive({ title: 'this is subpage' });
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
    .text {
        font-size: 20px;
        text-align: center;
    }
}
</style>
