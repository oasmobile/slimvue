const fs = require("fs"),
    path = require("path"),
    buildFileType = process.env.BUILD_FILE_TYPE || "twig",
    entriesDirPath = path.join(__dirname, "./src/entries");
const setPages = directoryPath => {
    let items = fs.readdirSync(directoryPath);
    if (items.length) {
        items.forEach(item => {
            let itemPath = path.join(directoryPath, item),
                stat = fs.statSync(itemPath);
            if (exculed.indexOf(itemPath) > -1) {
                return;
            }
            if (stat.isDirectory()) {
                setPages(itemPath);
            } else if (stat.isFile() && /\.js$/.test(item)) {
                let relativePath = path.relative(entriesDirPath, itemPath),
                    key = relativePath
                        .replace(".js", "")
                        .replace(/(\/|\\)/g, "-");

                pages[key] = {
                    entry: itemPath,
                    template: `template/index.${buildFileType}`,
                    filename:
                        "pages/" + relativePath.replace(/js$/, buildFileType),
                    title: key
                };
            }
        });
    }
};

let pages = {},
    exculed = process.env.EXCLUED_ENTRIES;
exculed = exculed ? exculed.split(",") : [];
exculed = exculed.map(item => {
    return path.join(entriesDirPath, item);
});

setPages(entriesDirPath);

module.exports = {
    pages,
    chainWebpack(config) {
        config.resolve.alias
            .set("slimvue", path.join(__dirname, "./slimvue.js"))
            .set("assets", path.join(__dirname, "./src/assets"));
    }
};
