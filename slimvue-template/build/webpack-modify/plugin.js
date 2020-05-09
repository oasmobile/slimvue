const path = require("path"),
    slimvueConfig = require("../config.js"),
    copyToDir = path.join(__dirname, "../..", slimvueConfig.outputDir, "static");

const modify = config => {
    config.plugin("copy").tap(args => {
        let opts = [...args[0]];
        opts.forEach(opt => (opt.to = copyToDir));
        return [opts];
    });
};

module.exports = modify;
