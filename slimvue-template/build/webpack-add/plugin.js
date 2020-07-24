const webpack = require("webpack");

module.exports = {
    "banner-plugin": {
        plugin: webpack.BannerPlugin,
        args: [
            {
                banner: "build from slimvue"
            }
        ]
    }
};
