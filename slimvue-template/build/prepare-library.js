require('./check-versions')();
const fs = require('fs-extra');
const resolveConfig = require('./resolve.conf');

// noinspection JSUnresolvedFunction
let packageInfo = fs.readJsonSync(resolveConfig.resolve('package.json'));
packageInfo.main = "src/publish.js";
packageInfo.scripts.postinstall = "node build/post-install.js";
packageInfo.private = false; // TODO: remove this line when private NPM publishing is available
fs.outputJsonSync(
    resolveConfig.resolve('package.json'),
    packageInfo,
    {spaces : 4}
);