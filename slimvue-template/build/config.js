const path = require("path");

let exculedEntries = process.env.EXCLUED_ENTRIES,
    entryDirectory = path.join(__dirname, "../src/entries"),
    templateDirectory = path.join(__dirname, "../template");

exculedEntries = exculedEntries ? exculedEntries.split(",") : [];
exculedEntries = exculedEntries.map(item => {
    return path.join(entryDirectory, item);
});

module.exports = {
    publicPath: process.env.PUBLIC_PATH || "/",
    outputDir: process.env.OUTPUT_DIR || "dist",
    assetsDir: process.env.ASSETS_DIR,
    buildFileType: process.env.BUILD_FILE_TYPE || "twig",
    exculedEntries,
    entryDirectory,
    templateDirectory
};
