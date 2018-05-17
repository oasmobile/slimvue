const config = require('../../config');
const CopyWebpackPlugin = require('copy-webpack-plugin');
const path = require('path');
const resolveConfig = require('../resolve.conf');
const fs = require("fs-extra");
const rread = require('recursive-readdir-sync');

let patterns = [
    {
        from   : path.resolve(__dirname, '../../static'),
        to     : path.join(config.build.buildOutputRoot, 'static'),
        ignore : ['.*'],
    },
];

let includedDir = resolveConfig.resolve('build/copy-file-settings');
if (fs.existsSync(includedDir)) {
    let copyFileSettings = rread(includedDir);
    // noinspection JSUnresolvedFunction
    copyFileSettings.forEach(filename => {
        let fileContent = fs.readJsonSync(filename);
        patterns.push = {
            from   : path.join(resolveConfig.resolve("node_modules"), fileContent.name, 'dist/', fileContent.name),
            to     : path.join(config.build.buildOutputRoot, fileContent.name),
            ignore : fileContent.ignores,
        };
    });
}

module.exports = new CopyWebpackPlugin(patterns);
