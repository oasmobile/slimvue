<template>
    <div id="app">
        <img alt="Vue logo" src="@/assets/logo.png" />
        <HelloWorld msg="Welcome to Your Vue.js App" />
        <div class="logo"></div>
    </div>
</template>

<script>
import HelloWorld from "./HelloWorld.vue";

export default {
    name: "App",
    components: {
        HelloWorld
    }
};
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
        background: url("~@/assets/logo.png") no-repeat;
    }
}
</style>
