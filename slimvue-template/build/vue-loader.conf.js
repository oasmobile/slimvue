let utils = require('./utils');
let config = require('../config');

module.exports = {
    loaders            : utils.cssLoaders({
        sourceMap : config.build.cssSourceMap,
        extract   : config.isProduction(),
    }),
    transformToRequire : {
        video  : 'src',
        source : 'src',
        img    : 'src',
        image  : 'xlink:href'
    }
};
