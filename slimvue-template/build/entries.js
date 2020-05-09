const fs = require("fs"),
    path = require("path"),
    config = require("./config"),
    tdk = require("./tdk"),
    getPages = directoryPath => {
        let items = fs.readdirSync(directoryPath),
            pages = {};
        if (items.length) {
            items.forEach(item => {
                let itemPath = path.join(directoryPath, item),
                    stat = fs.statSync(itemPath);
                if (config.exculedEntries.indexOf(itemPath) > -1) {
                    return;
                }
                if (stat.isDirectory()) {
                    Object.assign(pages, getPages(itemPath));
                } else if (stat.isFile() && /\.js$/.test(item)) {
                    let relativePath = path.relative(
                            config.entryDirectory,
                            itemPath
                        ),
                        key = relativePath
                            .replace(".js", "")
                            .replace(/(\/|\\)/g, "-");

                    pages[key] = Object.assign(
                        {
                            entry: itemPath,
                            template:
                                config.templateDirectory +
                                `/index.${config.buildFileType}`,
                            filename:
                                "pages/" +
                                relativePath.replace(
                                    /js$/,
                                    config.buildFileType
                                )
                        },
                        tdk[key]
                    );
                }
            });
        }
        return pages;
    };

let pages = getPages(config.entryDirectory);

module.exports = pages;
