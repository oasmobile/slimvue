let pages = require("./build/entries.js"),
    slimvueConfig = require("./build/config.js"),
    webpackConfig = require("./build/webpack-chain/index.js"),
    transformAssetUrls = require("./build/transformAssetUrls.js");

module.exports = {
    publicPath: slimvueConfig.publicPath,
    outputDir: slimvueConfig.outputDir,
    assetsDir: slimvueConfig.assetsDir,
    pages,
    chainWebpack(config) {
        config.merge(webpackConfig);
        config.module
            .rule("vue")
            .use("vue-loader")
            .tap(options => {
                options.transformAssetUrls = transformAssetUrls;
                return options;
            });
    }
};
