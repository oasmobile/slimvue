let pages = require("./build/entries"),
    slimvueConfig = require("./build/config"),
    webpackConfig = require("./build/webpack.conf.js"),
    transformAssetUrls = require("./build/transformAssetUrls");

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
