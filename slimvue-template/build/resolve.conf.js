"use strict";

const path = require('path');
const rread = require('recursive-readdir-sync');
const fs = require('fs-extra');

function resolve(dir) {
    return path.join(__dirname, '..', dir);
}

let alias = {
    'vue$'     : 'vue/dist/vue.esm.js',
    'src'      : resolve('src'),
    'slimvue$' : resolve('slimvue.js'),
    'assets'   : resolve('src/assets'),
    // 'components' : resolve('src/components'),
};

let includedDir = resolve('build/resolve-deps');
if (fs.existsSync(includedDir)) {
    let resolveDeps = rread();
    // noinspection JSUnresolvedFunction
    resolveDeps.forEach(filename => {
        // noinspection JSUnresolvedFunction
        let fileContent = fs.readJsonSync(filename);
        console.log(fileContent);
        for (let key in fileContent) {
            // noinspection JSUnfilteredForInLoop
            let path = fileContent[key];
            // noinspection JSUnfilteredForInLoop
            alias[key] = resolve(path);
        }
    });
}
// console.log(alias);

module.exports = {
    resolve : resolve,
    config  : {
        extensions : ['.js', '.vue', '.json'],
        alias      : alias,
    },
};
