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
        // return real web path
        return "http://my.project.com" + index;
    },
};
