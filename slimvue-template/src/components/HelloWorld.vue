<template>
    <div class="hello">
        <h1>{{ msg }}</h1>
        <p>
            For a guide and recipes on how to configure / customize this
            project,
            <br />
            check out the
            <a href="https://vite.dev" target="_blank" rel="noopener">
                Vite documentation </a
            >.
        </p>
        <h3>Installed Plugins</h3>
        <ul>
            <li>
                <a
                    href="https://github.com/vitejs/vite-plugin-vue"
                    target="_blank"
                    rel="noopener"
                >
                    @vitejs/plugin-vue
                </a>
            </li>
            <li>
                <a
                    href="https://vitest.dev"
                    target="_blank"
                    rel="noopener"
                >
                    vitest
                </a>
            </li>
        </ul>
        <h3>Essential Links</h3>
        <ul>
            <li>
                <a href="https://vuejs.org" target="_blank" rel="noopener">
                    Core Docs
                </a>
            </li>
            <li>
                <a
                    href="https://forum.vuejs.org"
                    target="_blank"
                    rel="noopener"
                >
                    Forum
                </a>
            </li>
            <li>
                <a href="https://chat.vuejs.org" target="_blank" rel="noopener">
                    Community Chat
                </a>
            </li>
        </ul>
        <h3>Ecosystem</h3>
        <ul>
            <li>
                <a
                    href="https://router.vuejs.org"
                    target="_blank"
                    rel="noopener"
                >
                    vue-router
                </a>
            </li>
            <li>
                <a
                    href="https://pinia.vuejs.org"
                    target="_blank"
                    rel="noopener"
                >
                    pinia
                </a>
            </li>
            <li>
                <a
                    href="https://devtools.vuejs.org"
                    target="_blank"
                    rel="noopener"
                >
                    vue-devtools
                </a>
            </li>
        </ul>
        <button @click="emit('greet', 'World')">Say Hello</button>
    </div>
</template>

<script setup>
const props = defineProps({
    msg: { type: String, required: true },
});

const emit = defineEmits(['greet']);
</script>

<style scoped lang="scss">
h3 {
    margin: 40px 0 0;
}
ul {
    list-style-type: none;
    padding: 0;
}
li {
    display: inline-block;
    margin: 0 10px;
}
a {
    color: #42b983;
}
button {
    margin-top: 20px;
    padding: 8px 16px;
    background-color: #42b983;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}
</style>
