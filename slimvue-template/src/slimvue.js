module.exports = {
    get bridge() {
        if (undefined === window.bridge) {
            return process.env.bridge;
        }
        else {
            return window.bridge;
        }
    },
};
