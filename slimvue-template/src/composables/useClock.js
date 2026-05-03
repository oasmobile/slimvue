import { ref, computed, onMounted, onUnmounted } from 'vue';

function prefixDateNum(num) {
    return num < 10 ? '0' + num : num.toString();
}

function formatDateTime(date) {
    const y = date.getFullYear();
    const m = prefixDateNum(date.getMonth() + 1);
    const d = prefixDateNum(date.getDate());
    const hh = prefixDateNum(date.getHours());
    const mm = prefixDateNum(date.getMinutes());
    const ss = prefixDateNum(date.getSeconds());
    return [y, m, d].join('-') + ' ' + [hh, mm, ss].join(':');
}

export function useClock() {
    const time = ref(Date.now());
    let timer = null;

    const fullDateTime = computed(() => {
        return formatDateTime(new Date(time.value));
    });

    onMounted(() => {
        timer = setInterval(() => {
            time.value = Date.now();
        }, 1000);
    });

    onUnmounted(() => {
        clearInterval(timer);
    });

    return { time, fullDateTime };
}

export { prefixDateNum, formatDateTime };
