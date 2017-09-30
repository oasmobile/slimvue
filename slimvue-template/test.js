"use strict";
let rreaddir = require('recursive-readdir');
let path = require('path');

// noinspection JSUnusedLocalSymbols
rreaddir(
    '/Users/minhao/git/vue-init-webpack/src',
    [
        (file, stats) => {
            // if (stats.isDirectory()) return true;
            file = path.relative('/Users/minhao/git/vue-init-webpack', file);
            console.log(file);
        },
    ],
    (err, files) => {
        if (err) throw err;

        console.log(files);
    }
)
;
