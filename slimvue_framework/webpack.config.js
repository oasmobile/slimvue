const CleanWebpackPlugin = require('clean-webpack-plugin');
const HtmlWebpackPlugin = require('html-webpack-plugin');
const path = require('path');
const webpack = require('webpack');
const gfs = require('graceful-fs');
const _ = require('lodash');

let controllers = gfs.readdirSync(path.resolve(__dirname, "src/controllers"));
let entries = {};
controllers.forEach(controllerFile => {
    let result = (/(.*)\.js$/).exec(controllerFile);
    if (result) {
        let name = `${result[1]}`;
        entries[name] = ('./src/controllers/' + controllerFile);
    }
});

module.exports = (env = 'htmlonly') => {
    let config = require(`./config/config.${env}.js`);

    console.log("Packing using env: " + env);
    console.log("Will pack for following entries: " + JSON.stringify(entries));

    let plugins = [
        new webpack.ProvidePlugin({}),
        new CleanWebpackPlugin(
            [config.targetDir],
            {
                root    : config.basePath,
                exclude : [".gitignore"]
            }
        ),
        // new TransferWebpackPlugin([
        //     {from : 'src/pages', to : "."},
        // ]),
        new webpack.optimize.CommonsChunkPlugin(
            {
                name      : 'vendor',
                minChunks : function (module) {
                    // This prevents stylesheet resources with the .css or .scss extension
                    // from being moved from their original chunk to the vendor chunk
                    // if (module.resource && (/^.*\.(css|scss)$/).test(module.resource)) {
                    //     return false;
                    // }
                    return module.context && module.context.indexOf("node_modules") !== -1;
                }
            }
        ),
        new webpack.optimize.CommonsChunkPlugin(
            {
                name   : 'commons',
                chunks : Object.keys(entries),
            }
        ),
        new webpack.optimize.CommonsChunkPlugin(
            {
                name      : 'manifest',
                minChunks : Infinity,
            }
        ),
        // new ExtractTextPlugin("css/my-styles.css"),

    ];
    plugins.push(new HtmlWebpackPlugin({
        chunksSortMode : 'manual',
        chunks         : ['manifest', 'vendor', 'commons'],
        filename       : path.join(config.outputBasePath, 'twigs', 'slimvue.twig'),
        template       : 'src/templates/slimvue.twig',
        inject         : 'head',
    }));
    _.each(entries, (file, entry) => {
        console.log("Creating entry " + entry + " based on file " + file);
        plugins.push(new HtmlWebpackPlugin({
            chunksSortMode : 'manual',
            chunks         : [],
            filename       : path.join(config.outputBasePath, 'twigs', `slimvue-${entry}.twig`),
            template       : 'src/templates/slimvue-page.twig',
        }));
        plugins.push(new HtmlWebpackPlugin({
            chunksSortMode : 'manual',
            chunks         : [entry],
            filename       : path.join(config.outputBasePath, 'twigs', `exec-slimvue-${entry}.twig`),
            template       : 'src/templates/slimvue-exec.twig',
        }));
        if (env === 'htmlonly') {
            plugins.push(new HtmlWebpackPlugin({
                title          : entry,
                chunksSortMode : 'manual',
                chunks         : ['manifest', 'vendor', 'commons', entry],
                filename       : path.join(config.outputBasePath, `${entry}.html`),
            }));
        }
    });

    return {
        context : config.basePath,
        resolve : {
            alias : {
                config : path.resolve(__dirname, `config/config.${env}.js`),
                bridge : path.resolve(__dirname, `src/bridge.${env}.js`),
                vue$   : 'vue/dist/vue.esm.js',
                src    : path.resolve(config.basePath, 'src/'),
                assets : path.resolve(config.basePath, 'src/assets/'),
            }
        },
        entry   : entries,
        output  : {
            path          : config.outputBasePath,
            publicPath    : config.publicBasePath,
            filename      : "js/[name].js?h=[chunkHash]",
            chunkFilename : "js/[name].[chunkhash].js"
        },
        module  : {
            rules : [
                {
                    test    : /\.vue$/,
                    loader  : 'vue-loader',
                    options : {
                        loaders : {
                            scss : 'style-loader!css-loader!sass-loader',
                        }
                    }
                },
                {
                    test    : /\.js$/,
                    use     : ['babel-loader'],
                    exclude : /node_modules/
                },
                {
                    test : /\.css$/,
                    use  : ['style-loader', 'css-loader', 'postcss-loader']
                },
                {
                    test    : /\.(eot|svg|ttf|woff|woff2)(\?\S*)?$/,
                    loader  : 'file-loader',
                    options : {
                        name       : '[name].[ext]?[hash]',
                        outputPath : "assets/",
                        publicPath : config.publicBasePath,
                    }
                },
                {
                    test    : /\.(png|jpe?g|gif|svg)(\?\S*)?$/,
                    loader  : 'file-loader',
                    options : {
                        name       : '[name].[ext]?[hash]',
                        outputPath : "img/",
                        publicPath : config.publicBasePath,
                    }
                },
            ],
        },
        plugins : plugins,
    };
};
