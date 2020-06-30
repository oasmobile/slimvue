<template>
    <div>{{fullDateTime}}</div>
</template>

<script>
    export default {
        name: "MyClock.vue",
        data() {
            return {
                time: Date.now()
            }
        },
        computed: {
            fullDateTime() {
                return this.getFullDateTime();
            }
        },
        methods: {
            getFullDateTime() {
                let obj = new Date(this.time),
                    y = obj.getFullYear(),
                    m = obj.getMonth(),
                    d = obj.getDate(),
                    hh = obj.getHours(),
                    mm = obj.getMinutes(),
                    ss = obj.getSeconds();
                m = this.prefixDateNum(m + 1);
                d = this.prefixDateNum(d);
                hh = this.prefixDateNum(hh);
                mm = this.prefixDateNum(mm);
                ss = this.prefixDateNum(ss);
                return [y, m, d].join('-') + ' ' + [hh, mm, ss].join(':');
            },
            prefixDateNum(num) {
                return num < 10 ? '0' + num : num.toString();
            }
        },
        created() {
            setInterval(() => {
                this.time = Date.now();
            }, 1000);
        }
    }
</script>

<style scoped>

</style>
