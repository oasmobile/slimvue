const path = require("path"),
    slimvueConfig = require("../config.js"),
    copyToDir = path.join(
        __dirname,
        "../..",
        slimvueConfig.outputDir,
        "static"
    );

const modify = config => {
    config.plugins.has("copy") &&
        config.plugin("copy").tap(args => {
            if (args.length) {
                let opts = [...args[0]];
                opts.forEach(opt => (opt.to = copyToDir));
                return [opts];
            }
            return args;
        });
};

module.exports = modify;
