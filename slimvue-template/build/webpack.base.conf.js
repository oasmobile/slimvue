const utils = require('./utils');
const config = require('../config');
const vueLoaderConfig = require('./vue-loader.conf');
const resolveConfig = require('./resolve.conf');
// const webpack = require('webpack');
// const merge = require('webpack-merge');

let resolve = resolveConfig.resolve;

let entries = config.getEntries();
// noinspection JSUnresolvedFunction
let webpackConfig = {
    entry   : entries,
    output  : {
        path          : config.build.buildOutputRoot,
        chunkFilename : utils.assetsPath("js/[name].[chunkhash:7].js"),
        filename      : utils.assetsPath('js/[name].js?h=[chunkhash:7]'),
        publicPath    : config.build.assetsPublicPath,
    },
    resolve : resolveConfig.config,
    module  : {
        rules : [
            {
                test    : /\.(js|vue)$/,
                loader  : 'eslint-loader',
                enforce : 'pre',
                include : [resolve('src'), resolve('test')],
                options : {
                    formatter : require('eslint-friendly-formatter')
                }
            },
            {
                test    : /\.vue$/,
                loader  : 'vue-loader',
                options : vueLoaderConfig
            },
            {
                test    : /\.js$/,
                loader  : 'babel-loader',
                include : [resolve('src'), resolve('test')]
            },
            {
                test    : /\.(png|jpe?g|gif|svg)(\?.*)?$/,
                loader  : 'url-loader',
                options : {
                    limit : 100,
                    name  : utils.assetsPath('img/[name].[hash:7].[ext]')
                }
            },
            {
                test    : /\.(mp4|webm|ogg|mp3|wav|flac|aac)(\?.*)?$/,
                loader  : 'url-loader',
                options : {
                    limit : 10000,
                    name  : utils.assetsPath('media/[name].[hash:7].[ext]')
                }
            },
            {
                test    : /\.(woff2?|eot|ttf|otf)(\?.*)?$/,
                loader  : 'url-loader',
                options : {
                    limit : 10000,
                    name  : utils.assetsPath('fonts/[name].[hash:7].[ext]')
                }
            }
        ]
    }
    ,
    plugins : ([]).concat(
        require('./plugins/define-plugin'),
        require('./plugins/common-trunks-plugin')
    ),
};
module.exports = webpackConfig;
