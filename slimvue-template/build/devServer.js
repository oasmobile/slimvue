const pages = require("./entries"),
    keys = Object.keys(pages),
    isEmptyPages = !keys.length;

module.exports = {
    port: 8090,
    open: process.env.NODE_ENV === "development",
    openPage: isEmptyPages ? "" : pages[keys[0]]["filename"]
};
