const path = require('path');

let targetDir = 'dist';
let basePath = path.resolve(__dirname, '..');
let outputBasePath = path.resolve(__dirname, '..', targetDir);
// resource referring path, like js, images and css
let publicBasePath = "/";

module.exports = {
    targetDir,
    basePath,
    outputBasePath,
    publicBasePath,
    routeGenerator(index) {
        // TODO: write your own rule to generate route according to index
        return "http://my.project.com" + index;
    },
};
