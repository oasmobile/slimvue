const webpackConfig = require('../webpack-add'),
    modifyPlugin = require('./plugin'),
    modifyModule = require('./module');

const modify = config => {
    config.merge(webpackConfig);
    modifyPlugin(config);
    modifyModule(config);
    return config;
};

module.exports = modify;