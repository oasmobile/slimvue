module.exports = {
    moduleFileExtensions: ["js", "json", "vue"],
    transform: {
        "^.+\\.vue$": "vue-jest",
        "^.+\\.js$": "babel-jest"
    },
    moduleNameMapper: {
        "^@/(.*)$": "<rootDir>/src/$1",
        "^slimvue$": "<rootDir>/slimvue.js",
        "^assets/(.*)$": "<rootDir>/src/assets/$1"
    },
    testMatch: ["<rootDir>/tests/**/*.test.js"],
    collectCoverageFrom: [
        "slimvue.js",
        "build/**/*.js",
        "src/components/**/*.vue",
        "!build/webpack-add/plugin.js"
    ],
    transformIgnorePatterns: ["/node_modules/"]
};
