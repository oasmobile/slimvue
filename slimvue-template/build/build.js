require('./check-versions')();
// noinspection JSUnusedGlobalSymbols
const argv = require('minimist')(process.argv.slice(2), {
    boolean : ['p', 'w'],
    default : {p : false, w : false},
    alias   : {
        'p' : ['production', 'prod'],
        'w' : ['watch'],
    },
    unknown : () => false,
});
// noinspection JSUnresolvedVariable
const isDebug = !argv.p;
process.env.NODE_ENV = isDebug ? 'development' : 'production';
let config = require('../config');
config.setDebug(isDebug);
const ora = require('ora');
// const rm = require('rimraf');
// const path = require('path');
// noinspection NpmUsedModulesInstalled
const chalk = require('chalk');
const webpack = require('webpack');
const webpackConfigFile = isDebug ? './webpack.dev.conf' : './webpack.prod.conf';
const webpackConfig = require(webpackConfigFile);
// noinspection JSUnresolvedVariable
webpackConfig['watch'] = argv.w;
// console.log(webpackConfig);
let spinner = ora(`building for ${process.env.NODE_ENV}...`);
spinner.start();

// rm(
//     path.join(config.build.buildOutputRoot, config.build.assetsSubDirectory),
//     err => {
//         if (err) throw err;
webpack(
    webpackConfig,
    function (err, stats) {
        spinner.stop();
        if (err) throw err;
        let format = {
            colors       : true,
            modules      : false,
            children     : false,
            chunks       : false,
            chunkModules : false,
        };
        // noinspection JSCheckFunctionSignatures
        process.stdout.write(stats.toString(format) + '\n\n');

        if (stats.hasErrors()) {
            // noinspection JSUnresolvedFunction
            console.log(chalk.red('  Build failed with errors.\n'));
            process.exit(1);
        }

        // noinspection JSUnresolvedFunction
        console.log(chalk.cyan('  Build complete.\n'));
        // noinspection JSUnresolvedFunction
        console.log(chalk.yellow(
            '  Tip: built files are meant to be served over an HTTP server.\n' +
            '  Opening index.html over file:// won\'t work.\n'
        ));
    }
);
//     }
// );
