const customTransforms = require('../config/transform-settings');
const merge = require('webpack-merge');
const utils = require('./utils');
const config = require('../config');

let transformToRequire = merge({
    video  : 'src',
    source : 'src',
    img    : 'src',
    image  : 'xlink:href',
}, customTransforms);

module.exports = {
    loaders            : utils.cssLoaders({
        sourceMap : config.build.cssSourceMap,
        extract   : config.isProduction(),
    }),
    transformToRequire : transformToRequire,
};
