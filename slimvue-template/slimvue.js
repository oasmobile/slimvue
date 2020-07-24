import Vue from "vue";

// noinspection JSUnresolvedVariable
Vue.config.productionTip = process.env.NODE_ENV !== "production";

Vue.prototype.$toCssUrl = function(imageUrl) {
    return "url(" + imageUrl + ")";
};
Vue.prototype.$toCssBackgroundImage = function(imageUrl) {
    return {
        backgroundImage: this.$toCssUrl(imageUrl)
    };
};

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
        } else {
            return this._logLevel;
        }
    },
    get isDebug() {
        return process.env.NODE_ENV !== "production";
    },
    get bridge() {
        if (undefined === window.bridge) {
            return JSON.parse(process.env.VUE_APP_BRIDGE);
        } else {
            return window.bridge;
        }
    },
    mount(vueComponent) {
        this.log("Will start to mount component to slimvue app", vueComponent);
        let app = (window.slimvue = new Vue({
            render: h => h(vueComponent)
        }).$mount("#app"));
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
    }
};
