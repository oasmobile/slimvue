"use strict";

const path = require('path');

function resolve(dir) {
    return path.join(__dirname, '..', dir);
}

module.exports = {
    resolve : resolve,
    config  : {
        extensions : ['.js', '.vue', '.json'],
        alias      : {
            'vue$'     : 'vue/dist/vue.esm.js',
            'src'      : resolve('src'),
            'slimvue$' : resolve('slimvue.js'),
            'assets'   : resolve('src/assets'),
            // 'components' : resolve('src/components'),
        },
    },
};
