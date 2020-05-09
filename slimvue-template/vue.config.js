let path = require("path"),
    pages = require("./build/entries.js"),
    slimvueConfig = require("./build/config.js"),
    webpackConfig = require("./build/webpack-chain/index.js"),
    devServer = require("./build/devServer.js"),
    transformAssetUrls = require("./build/transformAssetUrls.js"),
    copyToDir = path.join(__dirname, slimvueConfig.outputDir, "static");

module.exports = {
    publicPath: slimvueConfig.publicPath,
    outputDir: slimvueConfig.outputDir,
    assetsDir: slimvueConfig.assetsDir,
    pages,
    devServer,
    chainWebpack(config) {
        config.merge(webpackConfig);
        config.module
            .rule("vue")
            .use("vue-loader")
            .tap(options => {
                options.transformAssetUrls = transformAssetUrls;
                return options;
            });
        config.plugin("copy").tap(args => {
            let opts = [...args[0]];
            opts.forEach(opt => (opt.to = copyToDir));
            return [opts];
        });
    }
};
