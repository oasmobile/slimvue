const path = require('path');

let targetDir = 'dist';
let basePath = path.resolve(__dirname, '..');
let outputBasePath = path.resolve(__dirname, '..', targetDir);
// resource referring path, like js, images and css
let publicBasePath = path.join("/slimvue", targetDir, "/");

module.exports = {
    targetDir,
    basePath,
    outputBasePath,
    publicBasePath,
    routeGenerator(index) {
        // TODO: return webstorm debug page path
        return path.join("/slimvue", targetDir, index + ".html");
    },
};
