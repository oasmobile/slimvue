const utils = require('./utils');
const config = require('../config');
const merge = require('webpack-merge');
const baseWebpackConfig = require('./webpack.base.conf');

// noinspection JSUnresolvedFunction
module.exports = merge(baseWebpackConfig, {
    module  : {
        rules : utils.styleLoaders({sourceMap : config.build.cssSourceMap})
    },
    // cheap-module-eval-source-map is faster for development
    devtool : '#cheap-module-eval-source-map',
    plugins : ([]).concat(
        require('./plugins/clean-plugin'),
        require('./plugins/dev-plugin')
    )
});
