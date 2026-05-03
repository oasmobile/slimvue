# Requirements Document

全栈 breaking 升级（v4.0）的需求规格，覆盖 PHP 端、前端、迁移产出物三大领域。

---

## Introduction

SlimVue 是一个 PHP Composer 库，为 Slim/Silex 等 PHP 框架提供 Vue.js 前端集成方案。当前技术栈严重滞后——PHP >= 7.0、Vue 2（已 EOL）、Vue CLI 4（已停止维护）、Silex（已废弃）等。本次 release 4.0 是一次全栈 breaking 升级，目标是将所有依赖升级到最新大版本，全面采用现代语法，并产出面向下游用户的完整迁移工具链。

本文档基于 `docs/proposals/PRP-001-full-stack-upgrade.md` 和 `.kiro/specs/release-4.0/goal.md`（含 Q1–Q7 Clarification 决策）编写。

**不涉及的内容（Non-scope）：**

- 不改变 SlimVue 的功能定位和核心架构（Bridge 机制、多页面入口、CLI 工具）
- 不改变目录结构的顶层组织方式
- 不引入新的功能特性（`index.php` 示例增强和模板组件重新设计属于升级范畴内的改进）
- 不考虑旧版本兼容——这是一次 breaking 升级

---

## Glossary

- **SlimVue**: 本项目，PHP Composer 库 `oasis/slimvue`
- **Bridge**: PHP-JS 数据桥接机制，通过 `TwigBridgeInfo` 将 PHP 数据序列化为 JSON 注入 Twig 模板，前端通过 `slimvue.bridge` 读取
- **CLI_Tool**: SlimVue 提供的 Symfony Console CLI 工具，包含 `initialize` 和 `upgrade` 两个命令
- **Template_Project**: `slimvue-template/` 目录下的前端项目模板，作为 `initialize` 命令的源模板
- **Build_System**: 前端构建系统，当前为 Vue CLI (webpack)，目标为 Vite
- **Entry_Scanner**: 多页面入口扫描机制，扫描 `src/entries/` 下的 JS 文件生成独立页面
- **Migration_Doc**: 面向下游用户的迁移文档，记录所有 breaking changes 和手动迁移步骤
- **Migration_Validator**: 面向下游用户的迁移验证脚本，自动检查项目的迁移完成度
- **Migration_Script**: 面向下游用户的迁移脚本，自动执行可自动化的迁移步骤
- **PBT**: Property-Based Testing，基于属性的测试方法

---

## Requirements

### Requirement 1: PHP 运行时与依赖升级

**User Story:** 作为库维护者，我希望将所有 PHP 依赖升级到最新大版本并要求 PHP >= 8.5，以便项目能使用现代语言特性并获得活跃的安全支持。

#### Acceptance Criteria

1. THE SlimVue SHALL require PHP >= 8.5 in `composer.json` `require.php` field
2. THE SlimVue SHALL declare `phpunit/phpunit` ^13.0 in `composer.json` `require-dev`
3. THE SlimVue SHALL declare `symfony/console` at its latest major version in `composer.json` `require`
4. THE SlimVue SHALL declare `symfony/filesystem` at its latest major version in `composer.json` `require`
5. THE SlimVue SHALL declare `oasis/utils` at its latest major version in `composer.json` `require`
6. THE SlimVue SHALL declare `oasis/flysystem-wrappers` at its latest major version in `composer.json` `require`
7. THE SlimVue SHALL declare `twig/twig` at its latest major version in `composer.json` `require-dev`
8. THE SlimVue SHALL declare `oasis/http` (replacing `silex/silex`) in `composer.json` `require-dev`
9. THE SlimVue SHALL declare `giorgiosironi/eris` ~1.1 in `composer.json` `require-dev` for PBT support
10. WHEN `php74 $(which composer) install` is executed, THE SlimVue SHALL complete without dependency resolution errors


### Requirement 2: PHP 源码现代化

**User Story:** 作为库维护者，我希望所有 PHP 源码采用 PHP 8.5 语法特性，以便代码库符合现代惯用写法并充分利用新语言能力。

#### Acceptance Criteria

1. THE SlimVue SHALL use typed properties (with type declarations) for all class properties in `src/*.php`
2. THE SlimVue SHALL use constructor promotion where applicable in `src/*.php`
3. THE SlimVue SHALL use union types, intersection types, or standalone types for all method parameters and return types in `src/*.php`
4. THE SlimVue SHALL use `match` expressions instead of `switch` statements where applicable in `src/*.php`
5. THE SlimVue SHALL use `readonly` properties where applicable in `src/*.php`
6. THE SlimVue SHALL use named arguments where they improve readability in `src/*.php`
7. THE SlimVue SHALL remove all legacy PHPDoc block comments that are redundant with native type declarations
8. WHEN all PHP source files are updated, THE SlimVue SHALL pass `php74 vendor/bin/phpunit` without failures

### Requirement 3: PHPUnit 13 测试适配

**User Story:** 作为库维护者，我希望所有 PHP 测试兼容 PHPUnit 13，以便测试套件在新测试框架上正确运行。

#### Acceptance Criteria

1. THE SlimVue SHALL update `phpunit.xml` to conform to PHPUnit 13 configuration schema (removing deprecated attributes such as `verbose`, `forceCoversAnnotation`, `beStrictAboutCoversAnnotation`, `beStrictAboutTodoAnnotatedTests`)
2. THE SlimVue SHALL update all test classes in `tests/*.php` to use PHPUnit 13 API (adapting any deprecated assertion methods or annotations)
3. THE SlimVue SHALL adopt PHP 8.5 syntax features (typed properties, constructor promotion, return types) in all test files
4. WHEN `php74 vendor/bin/phpunit` is executed, THE SlimVue SHALL report all tests passing with zero failures and zero errors

### Requirement 4: PHP 端 Property-Based Testing

**User Story:** 作为库维护者，我希望使用 eris 1.1 为 PHP 组件添加 Property-Based Testing，以便系统性地验证边界情况和不变量。

#### Acceptance Criteria

1. THE SlimVue SHALL include PBT tests for `TwigBridgeInfo` covering the round-trip property: for all valid bridge data, `json_decode(render())` SHALL produce a value equivalent to the original input data
2. THE SlimVue SHALL include PBT tests for `TwigBridgeInfo::add()` covering the idempotence-like property: adding a key twice with the same value SHALL produce the same render output as adding it once
3. THE SlimVue SHALL include PBT tests for `TwigBridgeInfo::getExecTwig()` covering the metamorphic property: the output length change SHALL equal the difference in length between "controllers" and "pages" when the prefix matches, and zero otherwise
4. THE SlimVue SHALL include PBT tests for `SlimVueInitializeCommand` covering the invariant: the generated `package.json` SHALL always contain the specified project name and version "0.1.0"
5. THE SlimVue SHALL include PBT tests for `SlimVueUpgradeCommand` covering the invariant: after upgrade, the `package.json` SHALL preserve the original project name and version
6. WHEN `php74 vendor/bin/phpunit` is executed, THE SlimVue SHALL report all PBT tests passing
7. THE SlimVue SHALL achieve combined test coverage (UT + PBT) > 90% for all PHP source files in `src/`


### Requirement 5: Silex 替换为 `oasis/http` 并增强 `index.php` 示例

**User Story:** 作为库用户，我希望示例 `index.php` 使用 `oasis/http` 替代已废弃的 Silex，并作为包含多页面入口和 Bridge 数据类型演示的综合参考，以便快速理解如何集成 SlimVue。

#### Acceptance Criteria

1. THE SlimVue SHALL replace all Silex references in `index.php` with `oasis/http` equivalents
2. THE SlimVue SHALL demonstrate multiple route definitions in `index.php` (at minimum: a main page route and a subpage route)
3. THE SlimVue SHALL demonstrate various bridge data types in `index.php` (string, integer, float, boolean, array, nested object)
4. THE SlimVue SHALL demonstrate `TwigBridgeInfo::getExecTwig()` usage in `index.php`
5. THE SlimVue SHALL include error handling demonstration in `index.php`
6. WHEN the `index.php` is loaded with a properly configured `oasis/http` environment, THE SlimVue SHALL render Twig templates without errors

### Requirement 6: 前端依赖升级与 Node.js 版本约束

**User Story:** 作为库维护者，我希望将所有 npm 依赖升级到最新大版本并要求 Node.js >= 24，以便前端技术栈保持最新且安全。

#### Acceptance Criteria

1. THE Template_Project SHALL declare `vue` ^3 (latest major version) in `package.json` `dependencies`
2. THE Template_Project SHALL remove `core-js` from `package.json` `dependencies`
3. THE Template_Project SHALL remove all Vue CLI related packages (`@vue/cli-service`, `@vue/cli-plugin-babel`, `@vue/cli-plugin-eslint`) from `package.json` `devDependencies`
4. THE Template_Project SHALL remove all Babel related packages (`babel-core`, `babel-eslint`, `babel-jest`) from `package.json` `devDependencies`
5. THE Template_Project SHALL remove `vue-template-compiler` from `package.json` `devDependencies` (Vue 3 uses `@vue/compiler-sfc` bundled with `vue`)
6. THE Template_Project SHALL declare `vite` and `@vitejs/plugin-vue` at their latest major versions in `package.json` `devDependencies`
7. THE Template_Project SHALL declare `vitest`, `@vue/test-utils` (^2, Vue 3 compatible), and `fast-check` at their latest major versions in `package.json` `devDependencies`
8. THE Template_Project SHALL declare `eslint` at its latest major version with flat config support in `package.json` `devDependencies`
9. THE Template_Project SHALL declare `prettier` at its latest major version in `package.json` `devDependencies`
10. THE Template_Project SHALL declare `sass` at its latest major version in `package.json` `devDependencies` (removing `sass-loader`, as Vite handles Sass natively)
11. THE Template_Project SHALL declare `"engines": { "node": ">=24" }` in `package.json`
12. THE Build_System SHALL include a Node.js version check script that verifies `process.version` >= 24 and exits with a descriptive error message when the constraint is not met
13. WHEN `npm install` is executed in `slimvue-template/`, THE Template_Project SHALL complete without dependency resolution errors

### Requirement 7: Vue CLI (webpack) 迁移到 Vite

**User Story:** 作为库维护者，我希望用 Vite 替代 Vue CLI (webpack) 作为构建工具，以便前端获得更快的构建速度和官方推荐的工具链。

#### Acceptance Criteria

1. THE Template_Project SHALL contain a `vite.config.js` (or `vite.config.ts`) at the project root that replaces `vue.config.js`
2. THE Build_System SHALL support multi-page entry scanning from `src/entries/` directory, replicating the current Entry_Scanner behavior
3. THE Build_System SHALL support the `BUILD_FILE_TYPE` environment variable to control output file type (`twig` or `html`)
4. THE Build_System SHALL support the `PUBLIC_PATH` environment variable to control the public base path
5. THE Build_System SHALL support the `OUTPUT_DIR` environment variable to control the output directory (default: `dist`)
6. THE Build_System SHALL support the `EXCLUED_ENTRIES` environment variable to exclude specific entries
7. THE Build_System SHALL output page files to `dist/pages/` with the same naming convention as the current webpack build
8. THE Build_System SHALL support TDK (title, description, keywords) metadata injection per page entry
9. THE Template_Project SHALL remove `vue.config.js`, `babel.config.js`, and the entire `build/` directory
10. THE Template_Project SHALL update `package.json` scripts to use Vite commands (`vite` for dev, `vite build` for production, `vite preview` for preview)
11. THE Template_Project SHALL include the Node.js version check in the `build` and `release` scripts
12. WHEN `npm run build` is executed, THE Build_System SHALL produce output in `dist/` with the correct multi-page structure


### Requirement 8: Vue 2 迁移到 Vue 3

**User Story:** 作为库维护者，我希望将前端从 Vue 2 迁移到 Vue 3 并重新设计模板组件，以便模板项目展示现代 Vue 3 模式和特性。

#### Acceptance Criteria

1. THE Template_Project SHALL rewrite `slimvue.js` to use Vue 3 API: `createApp()` instead of `new Vue()`, `app.config.globalProperties` instead of `Vue.prototype`
2. THE Template_Project SHALL redesign all template components using `<script setup>` syntax with Composition API
3. THE Template_Project SHALL demonstrate `defineProps` and `defineEmits` usage in at least one component
4. THE Template_Project SHALL demonstrate Vue 3 reactive API (`ref`, `reactive`, `computed`, `watch`) in at least one component
5. THE Template_Project SHALL demonstrate composables (extracted reusable logic as `use*` functions) in at least one component
6. THE Template_Project SHALL update all entry files in `src/entries/` to use the new `slimvue.mount()` API (which internally uses `createApp`)
7. THE Template_Project SHALL update `template/index.html` (and `template/index.twig`) to be compatible with Vite's HTML entry mechanism
8. WHEN a component is mounted via `slimvue.mount()`, THE SlimVue SHALL create a Vue 3 app instance, mount it to `#app`, and assign it to `window.slimvue`

### Requirement 9: Jest 迁移到 Vitest

**User Story:** 作为库维护者，我希望用 Vitest 替代 Jest 作为前端测试框架，以便测试与 Vite 原生集成并获得更快的执行速度。

#### Acceptance Criteria

1. THE Template_Project SHALL contain a Vitest configuration (in `vite.config.js` or a separate `vitest.config.js`)
2. THE Template_Project SHALL remove `jest.config.js` and all Jest-related dependencies
3. THE Template_Project SHALL update all test files in `tests/` to use Vitest API (replacing any Jest-specific APIs such as `jest.fn()` with `vi.fn()`, `jest.spyOn()` with `vi.spyOn()`, etc.)
4. THE Template_Project SHALL update `package.json` test scripts to use Vitest (`vitest run` for single execution, `vitest` for watch mode)
5. WHEN `npx vitest run` is executed in `slimvue-template/`, THE Template_Project SHALL report all tests passing with zero failures

### Requirement 10: 前端 Property-Based Testing

**User Story:** 作为库维护者，我希望使用 fast-check 为前端组件添加 Property-Based Testing，以便系统性地验证边界情况和不变量。

#### Acceptance Criteria

1. THE Template_Project SHALL include PBT tests for `slimvue.js` bridge getter covering the round-trip property: for all valid JSON-serializable objects set on `window.bridge`, the `bridge` getter SHALL return an equivalent object
2. THE Template_Project SHALL include PBT tests for `slimvue.js` log level covering the invariant: for all valid log level values, the `logLevel` setter followed by getter SHALL return the same value
3. THE Template_Project SHALL include PBT tests for the multi-page entry scanner covering the metamorphic property: adding an entry file SHALL increase the number of generated pages by exactly one
4. THE Template_Project SHALL include PBT tests for the TDK metadata injection covering the invariant: for all valid TDK objects, the generated HTML SHALL contain the specified title, keywords, and description
5. WHEN `npx vitest run` is executed, THE Template_Project SHALL report all PBT tests passing
6. THE Template_Project SHALL achieve combined test coverage (UT + PBT) > 90% for `slimvue.js` and all build configuration modules

### Requirement 11: ESLint 与 Prettier 升级

**User Story:** 作为库维护者，我希望将 ESLint 和 Prettier 升级到最新大版本并采用 flat config，以便代码风格工具保持最新且一致。

#### Acceptance Criteria

1. THE Template_Project SHALL replace `.eslintrc.js` with `eslint.config.js` (ESLint flat config format)
2. THE Template_Project SHALL configure ESLint with Vue 3 compatible rules (using `eslint-plugin-vue` with Vue 3 recommended config)
3. THE Template_Project SHALL configure Prettier integration via the latest `eslint-plugin-prettier` or `eslint-config-prettier`
4. THE Template_Project SHALL update `prettier.config.js` to use Prettier latest major version options
5. WHEN `npm run lint` is executed, THE Template_Project SHALL report zero errors on the upgraded codebase


### Requirement 12: CLI 工具适配

**User Story:** 作为库维护者，我希望 CLI 工具（`bin/slimvue`）适配升级后的 Symfony Console 和新的 Vite 模板，以便 `initialize` 和 `upgrade` 命令正常工作。

#### Acceptance Criteria

1. THE CLI_Tool SHALL adapt `bin/slimvue` to use Symfony Console latest major version API
2. THE CLI_Tool SHALL update `SlimVueInitializeCommand` to mirror the new Vite-based template (excluding `build/`, `vue.config.js`, `babel.config.js`, `jest.config.js`, and other removed files)
3. THE CLI_Tool SHALL update `SlimVueInitializeCommand` output messages to reference Vite commands instead of webpack commands (e.g., `npm run dev` instead of `npm run serve`)
4. THE CLI_Tool SHALL update `SlimVueUpgradeCommand` to handle the transition from webpack-based to Vite-based template structure (removing obsolete files from the target directory during upgrade)
5. THE CLI_Tool SHALL adopt PHP 8.5 syntax features in both command classes
6. WHEN `bin/slimvue initialize testproj` is executed, THE CLI_Tool SHALL create a valid Vite-based project directory
7. WHEN `bin/slimvue upgrade <existing-project>` is executed, THE CLI_Tool SHALL update the project to the new Vite-based template while preserving the project's `name`, `version`, `dependencies`, and `devDependencies`

### Requirement 13: 测试覆盖率与 CI 产物清理

**User Story:** 作为库维护者，我希望将测试覆盖率报告从版本控制中排除，并确保整体覆盖率超过 90%，以便仓库保持整洁且代码质量有保障。

#### Acceptance Criteria

1. THE SlimVue SHALL include `slimvue-template/coverage/` in `.gitignore`
2. THE SlimVue SHALL remove `slimvue-template/coverage/` from git tracking (via `git rm -r --cached`)
3. THE SlimVue SHALL achieve combined test coverage (UT + PBT + Integration) > 90% for PHP source files in `src/`
4. THE Template_Project SHALL achieve combined test coverage (UT + PBT + Integration) > 90% for frontend source files (`slimvue.js`, build config modules, Vue components)
5. WHEN `php74 vendor/bin/phpunit --coverage-text` is executed, THE SlimVue SHALL report line coverage > 90%
6. WHEN `npx vitest run --coverage` is executed, THE Template_Project SHALL report line coverage > 90%

### Requirement 14: 迁移文档

**User Story:** 作为下游用户，我希望有一份全面的迁移文档列出所有 breaking changes 和手动迁移步骤，以便将项目从 v3 升级到 v4。

#### Acceptance Criteria

1. THE Migration_Doc SHALL list all PHP breaking changes (PHP version requirement, removed/replaced dependencies, API changes in Symfony Console, Symfony Filesystem, oasis/utils, oasis/flysystem-wrappers, Twig)
2. THE Migration_Doc SHALL list all frontend breaking changes (Vue 2 → Vue 3 API changes, webpack → Vite configuration changes, Jest → Vitest test migration, ESLint flat config migration)
3. THE Migration_Doc SHALL list all `slimvue.js` API changes (Vue.prototype → app.config.globalProperties, `new Vue()` → `createApp()`, mount behavior changes)
4. THE Migration_Doc SHALL list all CLI command behavior changes (new template structure, removed files, updated output messages)
5. THE Migration_Doc SHALL provide step-by-step manual migration instructions for each breaking change
6. THE Migration_Doc SHALL be placed in `docs/changes/4.0/` directory

### Requirement 15: 迁移验证脚本

**User Story:** 作为下游用户，我希望有一个自动化验证脚本检查项目的迁移完成度，以便确认所有必要的变更已经应用。

#### Acceptance Criteria

1. THE Migration_Validator SHALL check PHP version constraint in the downstream project's `composer.json`
2. THE Migration_Validator SHALL check for deprecated dependency references (Silex, Vue 2, Vue CLI, Jest, old ESLint config)
3. THE Migration_Validator SHALL check for deprecated API usage patterns in PHP files (Silex classes, old Symfony Console API)
4. THE Migration_Validator SHALL check for deprecated API usage patterns in JS/Vue files (Vue 2 Options API patterns, `new Vue()`, `Vue.prototype`, Jest API)
5. THE Migration_Validator SHALL check for removed files that should no longer exist (`vue.config.js`, `babel.config.js`, `jest.config.js`, `build/` directory)
6. THE Migration_Validator SHALL output a summary report with pass/fail status for each check and actionable remediation hints
7. THE Migration_Validator SHALL be executable as a standalone script (e.g., `php bin/slimvue-migrate-check <project-dir>` or a shell script)

### Requirement 16: 迁移脚本

**User Story:** 作为下游用户，我希望有一个自动化迁移脚本执行可自动化的迁移步骤，以便减少从 v3 升级到 v4 时的手动工作量。

#### Acceptance Criteria

1. THE Migration_Script SHALL update `composer.json` PHP version constraint to >= 8.5
2. THE Migration_Script SHALL replace deprecated Composer dependency references with their new equivalents
3. THE Migration_Script SHALL remove obsolete frontend files (`vue.config.js`, `babel.config.js`, `jest.config.js`, `.eslintrc.js`, `build/` directory)
4. THE Migration_Script SHALL perform basic code transformations in JS files (replacing `new Vue(` with `createApp(`, `Vue.prototype` with `app.config.globalProperties`)
5. THE Migration_Script SHALL update `package.json` scripts from Vue CLI commands to Vite commands
6. THE Migration_Script SHALL create a backup of modified files before applying changes
7. THE Migration_Script SHALL output a log of all changes made and any items requiring manual attention
8. THE Migration_Script SHALL be executable as a standalone script (e.g., `php bin/slimvue-migrate <project-dir>` or a shell script)


---

## Socratic Review Log

### Round 1 — Self Review

**Q1: goal.md 中 Q1–Q7 的所有决策是否都已体现？**

- Q1（全部 scope）→ 体现：16 个 Requirement 覆盖 PHP 4 阶段 + 前端 4 阶段 + 迁移产出物
- Q2（迁移产出物为正式交付物）→ 体现：Req 14/15/16 分别覆盖文档、验证脚本、迁移脚本
- Q3（eris 1.1）→ 体现：Req 1 AC9 声明 eris，Req 4 覆盖 PHP PBT
- Q4（`index.php` 增强）→ 体现：Req 5 要求多路由、多数据类型演示
- Q5（重新设计模板组件）→ 体现：Req 8 要求 `<script setup>`、Composition API、composables、`defineProps`/`defineEmits`
- Q6（Node >= 24，engines + 构建脚本双重检查）→ 体现：Req 6 AC11/AC12，Req 7 AC11
- Q7（coverage 从 git 移除）→ 体现：Req 13 AC1/AC2

**Q2: 是否存在遗漏的 EARS 模式或不合规的 AC？**

逐条检查：所有 AC 均使用 THE/WHEN/WHILE/IF 开头，符合 EARS 六种模式之一。无 vague terms、无 pronouns、无 escape clauses。

**Q3: PBT 选型是否合理？**

- PHP 端 `TwigBridgeInfo` 的 round-trip（`json_decode(render())` ≈ input）是经典 serializer 属性，适合 PBT
- 前端 bridge getter 的 round-trip 同理
- Entry scanner 的 metamorphic property（加一个文件 → 多一个页面）适合 PBT
- CLI 命令的 invariant（name/version 保持）适合 PBT
- 不对外部服务（npm install、composer install）做 PBT，仅做 example-based 验证 ✓

**Q4: 是否有 Non-Goals 被意外引入？**

检查：无新功能引入，所有 Requirement 均围绕"升级"和"迁移"。`index.php` 增强和模板组件重新设计属于 goal.md 明确的升级范畴内改进。

**Q5: 测试覆盖率要求是否一致？**

Req 4 AC7、Req 10 AC6、Req 13 AC3/AC4/AC5/AC6 均要求 > 90%，与 goal.md 一致。

**结论**: 所有决策已体现，无遗漏，无违规。

### Round 2 — Gatekeeper 补充审查

**Q6: 是否有遗漏的错误路径或边界条件？**

- Req 1 AC10 仅验证 `composer install` 成功，未覆盖依赖冲突时的错误提示。但依赖冲突属于 Composer 自身行为，不需要 SlimVue 额外处理。合理。
- Req 5 AC6 验证 Twig 渲染无错误，但未覆盖 `oasis/http` 路由 404 或异常路由的行为。不过 `index.php` 是演示文件而非生产代码，错误处理演示（AC5）已足够。合理。
- Req 12 AC7 要求 `upgrade` 保留 `name`、`version`、`dependencies`、`devDependencies`，但未明确当目标项目缺少这些字段时的行为。这属于 design 阶段的边界处理决策。
- Req 16 AC6 要求备份，但未明确备份失败（如磁盘空间不足）时的行为。这属于 design 阶段的错误恢复策略。

**Q7: 各 Requirement 之间是否存在矛盾或重叠？**

- Req 2（PHP 源码现代化）和 Req 3（PHPUnit 13 适配）都涉及 PHP 8.5 语法。Req 2 聚焦 `src/`，Req 3 AC3 聚焦 `tests/`，无重叠。
- Req 4 AC7 和 Req 13 AC3 都要求 PHP 覆盖率 > 90%。Req 4 聚焦 UT + PBT，Req 13 聚焦 UT + PBT + Integration。Req 13 是超集，Req 4 AC7 可视为阶段性目标。无矛盾。
- Req 6 AC6 声明 Vite 依赖，Req 7 AC1 要求 `vite.config.js`。逻辑上 Req 6 是前提，Req 7 是使用。无矛盾。

**Q8: 是否有隐含的前置假设未显式列出？**

- 所有 PHP 命令使用 `php74` alias——这是 PROJECT.md 中记录的本机约束，但 v4.0 要求 PHP 8.5，`php74` alias 将不再适用。这是一个需要在 design 阶段解决的环境约束问题，不影响 requirements 的正确性（AC 中引用 `php74` 是沿用当前 PROJECT.md 的约定，实际执行时需要更新）。
- Req 7 AC2 要求 Entry_Scanner 行为与当前 webpack 构建一致，但当前行为的完整规格在 `docs/state/data-model.md` 中已有记录。无遗漏。

**Q9: scope 边界是否清晰？**

- `index.php` 增强的边界：Req 5 明确了最低要求（多路由、多数据类型、getExecTwig 演示、错误处理），不会无限膨胀。清晰。
- 模板组件重新设计的边界：Req 8 明确了必须展示的 Vue 3 特性（`<script setup>`、`defineProps`/`defineEmits`、reactive API、composables），但未限定组件数量。这留给 design 阶段决定。合理。
- 迁移脚本的边界：Req 16 列出了具体的自动化步骤，不会无限扩展。清晰。

**结论**: 无重大遗漏。`php74` alias 与 PHP 8.5 的矛盾是已知的环境约束，将在 design 阶段处理。


---

## Gatekeep Log

**校验时间**: 2026-05-03
**校验结果**: ⚠️ 已修正后通过

### 修正项

- [语体] 全部 16 条 User Story 从英文改为中文（`作为 <角色>，我希望 <能力>，以便 <价值>`）
- [结构] Introduction 补充 Non-scope 段落，明确列出不涉及的内容（来源：goal.md Non-Goals）
- [内容] Socratic Review 补充 Round 2（Gatekeeper 补充审查），覆盖错误路径/边界条件、Requirement 间矛盾/重叠、隐含假设、scope 边界清晰度

### 合规检查

- [x] 无 TBD / TODO / 待定 / 占位符
- [x] 无空 section 或不完整的列表
- [x] 内部引用一致（requirement 编号、术语表术语在正文中使用）
- [x] 无 markdown 格式错误
- [x] 一级标题存在且正确
- [x] Introduction 存在，描述了 feature 范围
- [x] Introduction 明确了不涉及的内容（Non-scope）
- [x] Glossary 存在且非空，格式正确
- [x] Requirements section 存在且包含 16 条 requirement
- [x] 各 section 之间使用 `---` 分隔
- [x] Glossary 中的术语在正文 AC 中被实际使用（无孤立术语）
- [x] AC 中使用的领域概念在 Glossary 中有定义
- [x] 所有 AC 使用 EARS 模式（THE/WHEN/IF 开头）
- [x] AC 编号连续，无跳号
- [x] User Story 使用中文行文
- [x] goal.md Q1–Q7 所有决策均已体现
- [x] 无 Non-Goals 被意外引入
- [x] 测试覆盖率要求与 goal.md 一致（> 90%）
- [○] 内容边界：部分 AC 引用了具体方法名（如 `TwigBridgeInfo::add()`），但这些是 SSOT（data-model.md）中记录的公共 API，作为 PBT 属性描述的 Subject 可接受

### Clarification Round

**状态**: 已回答

**Q1:** Req 12 AC7 要求 `upgrade` 命令保留目标项目的 `name`、`version`、`dependencies`、`devDependencies`。当目标项目的 `package.json` 缺少这些字段（或 `package.json` 本身不存在）时，`upgrade` 命令应如何处理？
- A) 缺少的字段使用模板默认值填充，继续执行
- B) 检测到缺失时中止并报错，要求用户手动修复后重试
- C) 缺少的字段跳过保留逻辑（即直接使用模板值），但在输出中警告用户
- D) 其他（请说明）

**A:** B — 检测到缺失时中止并报错，要求用户手动修复后重试

**Q2:** Req 16（迁移脚本）AC4 要求对 JS 文件执行基础代码转换（如 `new Vue(` → `createApp(`）。当用户项目中存在非标准写法（如多行 `new Vue` 调用、注释中的 `new Vue`、字符串中的 `Vue.prototype`）时，迁移脚本应采取什么策略？
- A) 仅做简单的字符串替换，不处理复杂情况，在日志中提示用户手动检查
- B) 使用 AST 解析进行精确转换，确保只替换实际代码中的调用
- C) 简单字符串替换 + 正则排除注释和字符串，兼顾准确性和实现成本
- D) 其他（请说明）

**A:** B — 使用 AST 解析进行精确转换，确保只替换实际代码中的调用

**Q3:** 当前 PROJECT.md 中所有 PHP 命令使用 `php74` alias（对应 PHP 7.4 运行环境），但 v4.0 要求 PHP >= 8.5。在 design 阶段，PHP 命令的执行方式应如何处理？
- A) 更新 PROJECT.md，将 `php74` alias 替换为新的 alias（如 `php85`），所有命令和文档同步更新
- B) 移除 alias 约定，直接使用 `php` 命令（假设系统默认 PHP 版本已升级到 8.5+）
- C) 保留 alias 机制但重命名，同时在 `composer.json` scripts 中封装命令以屏蔽 alias 差异
- D) 其他（请说明）

**A:** B — 移除 alias 约定，直接使用 `php` 命令（假设系统默认 PHP 版本已升级到 8.5+）

**Q4:** Req 5 AC5 要求 `index.php` 包含错误处理演示。这里的"错误处理"具体指什么层级？
- A) 仅演示 PHP 异常捕获和友好错误页面渲染（应用层错误处理）
- B) 演示 `oasis/http` 框架级错误处理（404 路由、500 异常中间件等）
- C) 同时演示应用层和框架级错误处理
- D) 其他（请说明）

**A:** C — 同时演示应用层和框架级错误处理
