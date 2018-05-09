const utils = require('./utils');
const config = require('../config');
const merge = require('webpack-merge');
const baseWebpackConfig = require('./webpack.base.conf');

// noinspection JSUnresolvedFunction
let webpackConfig = merge(baseWebpackConfig, {
    module    : {
        rules : utils.styleLoaders({
            sourceMap : config.build.cssSourceMap,
            extract   : true,
        }),
    },
    devtool   : config.build.cssSourceMap ? '#source-map' : false,
    entry     : resolveConfig.resolve('src/publish.js'),
    output    : {
        path          : config.build.buildOutputRoot,
        filename      : 'publish.js',
        publicPath    : config.build.assetsPublicPath,
        libraryTarget : 'umd',
    },
    externals : {
        moment : 'moment',
    },
    plugins   : ([]).concat(
        require('./plugins/clean-plugin'),
        require('./plugins/optimize-plugin')
    ),
});

module.exports = webpackConfig;
