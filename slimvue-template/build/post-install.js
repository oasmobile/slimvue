// console.log("This is run as POST INSTALL!");

const fs = require('fs-extra');
const resolveConfig = require('./resolve.conf');

let inHomeDir = (fs.pathExistsSync(resolveConfig.resolve('node_modules')));
let packageInfo = fs.readJsonSync(resolveConfig.resolve('package.json'));
let depFilePath, depSrcPath;

if (inHomeDir) {
    console.log("Will update self resolve dependency file ...");
    depFilePath = resolveConfig.resolve('build/resolve-deps/' + packageInfo.name + '.json');
    depSrcPath = "src";
}
else {
    let transformToRequireFilePath = resolveConfig.resolve('config/transform-settings.json');
    if (fs.existsSync(transformToRequireFilePath)) {
        console.log("Will generate transform to require file ...");
        let targetTransformToRequireFilePath = resolveConfig.resolve('../../build/custom-transform-to-require-settings/' + packageInfo.name + '.json');
        fs.copySync(transformToRequireFilePath, targetTransformToRequireFilePath);
        console.log("Custom transform to require file copied");
    }
    
    console.log("Will generate resolve dependency file ...");
    depFilePath = resolveConfig.resolve('../../build/resolve-deps/' + packageInfo.name + '.json');
    depSrcPath = "node_modules/" + packageInfo.name + "/src";
}

fs.outputJsonSync(
    depFilePath,
    {
        [packageInfo.name] : depSrcPath
    },
    {spaces : 4}
);
console.log("Resolve dependency file updated for " + packageInfo.name);
