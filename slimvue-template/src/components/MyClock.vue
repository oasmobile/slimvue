<template>
    <div>{{ fullDateTime }}</div>
</template>

<script setup>
import { useClock } from '../composables/useClock';

const { fullDateTime } = useClock();
</script>

<style scoped></style>
