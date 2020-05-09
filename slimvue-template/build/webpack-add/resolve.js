const path = require("path"),
    resolve = dir => path.join(__dirname, "../..", dir);

module.exports = {
    alias: {
        slimvue: resolve("slimvue.js"),
        assets: resolve("src/assets")
    }
};
