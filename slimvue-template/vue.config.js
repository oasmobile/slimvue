let path = require("path"),
    pages = require("./build/entries.js"),
    devServer = require("./build/devServer.js"),
    slimvueConfig = require("./build/config.js"),
    modifyWebpackConfig = require("./build/webpack-modify/index.js");

module.exports = {
    publicPath: slimvueConfig.publicPath,
    outputDir: slimvueConfig.outputDir,
    assetsDir: slimvueConfig.assetsDir,
    pages,
    devServer,
    chainWebpack(config) {
        modifyWebpackConfig(config);
    }
};
