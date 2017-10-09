"use strict";

const path = require('path');

function resolve(dir) {
    return path.join(__dirname, '..', dir)
}

module.exports = {
    resolve : resolve,
    config  : {
        extensions : ['.js', '.vue', '.json'],
        alias      : {
            'vue$'       : 'vue/dist/vue.esm.js',
            '@'          : resolve('src'),
            'assets'     : resolve('src/assets'),
            'components' : resolve('src/components'),
            'slimvue$'   : resolve('src/slimvue.js'),
        }
    }
};