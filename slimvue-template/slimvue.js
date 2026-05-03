import { createApp } from 'vue';

export default {
    DEBUG_LOG_LEVEL: 0,
    DEFAULT_LOG_LEVEL: 10,
    INFO_LOG_LEVEL: 20,
    WARNING_LOG_LEVEL: 30,
    ERROR_LOG_LEVEL: 40,

    _logLevel: -1,

    set logLevel(val) {
        this._logLevel = val;
    },
    get logLevel() {
        if (this._logLevel === -1) {
            return this.isDebug ? this.DEBUG_LOG_LEVEL : this.INFO_LOG_LEVEL;
        }
        return this._logLevel;
    },

    get isDebug() {
        return import.meta.env.MODE !== 'production';
    },

    get bridge() {
        if (typeof window.bridge === 'undefined') {
            return JSON.parse(import.meta.env.VITE_BRIDGE);
        }
        return window.bridge;
    },

    mount(vueComponent) {
        this.log('Will start to mount component to slimvue app', vueComponent);
        const app = createApp(vueComponent);
        app.config.globalProperties.$toCssUrl = (imageUrl) =>
            `url(${imageUrl})`;
        app.config.globalProperties.$toCssBackgroundImage = (imageUrl) => ({
            backgroundImage: `url(${imageUrl})`,
        });
        app.mount('#app');
        window.slimvue = app;
        return app;
    },

    log(...args) {
        if (this.logLevel <= this.DEFAULT_LOG_LEVEL) console.log(...args);
    },
    debug(...args) {
        if (this.logLevel <= this.DEBUG_LOG_LEVEL) console.debug(...args);
    },
    info(...args) {
        if (this.logLevel <= this.INFO_LOG_LEVEL) console.info(...args);
    },
    warn(...args) {
        if (this.logLevel <= this.WARNING_LOG_LEVEL) console.warn(...args);
    },
    error(...args) {
        if (this.logLevel <= this.ERROR_LOG_LEVEL) console.error(...args);
    },
};
