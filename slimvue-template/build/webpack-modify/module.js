const transformAssetUrls = require("../transformAssetUrls.js");

const modify = config => {
    config.module
        .rule("vue")
        .use("vue-loader")
        .tap(options => {
            options.transformAssetUrls = transformAssetUrls;
            return options;
        });
};

module.exports = modify;
