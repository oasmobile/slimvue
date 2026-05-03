# PRP-001 Full Stack Upgrade

全栈依赖升级 proposal，覆盖 PHP 端和前端体系的大版本迁移。

---

## Status

`released`

---

## Background

SlimVue 当前技术栈版本严重滞后：

| 层 | 当前版本 | 目标版本 |
|----|----------|----------|
| PHP | >= 7.0 | >= 8.5 |
| PHPUnit | ^9.0 | ^13.0 |
| Symfony Console | ^4.0 | ^8.0 |
| Symfony Filesystem | ^4.0 | ^8.0 |
| Twig | ^1.0 | 最新大版本 |
| Silex | ^2.2 | 替换为 `oasis/http`（Silex 已废弃） |
| oasis/utils | ^1.7 | 最新大版本 |
| oasis/flysystem-wrappers | ^1.3 | 最新大版本 |
| Vue.js | ^2.6 | Vue 3（最新大版本） |
| Vue CLI Service | ^4.3 | 替换为 Vite（Vue CLI 已停止维护） |
| Jest | ^26.6 | 替换为 Vitest |
| ESLint | ^6.7 | 最新大版本（flat config） |
| Prettier | ^1.19 | 最新大版本 |
| Sass / sass-loader | ^1.26 / ^8.0 | 最新大版本（Vite 内置 Sass 支持） |
| core-js | ^3.6 | 移除（现代浏览器 + Vite 不再需要） |
| Node.js | 未约束 | >= 20 |

多个依赖已进入 EOL 或停止维护（Silex、Vue 2、Vue CLI），继续使用存在安全风险和生态脱节。

---

## Problem

1. PHP 7.0 已 EOL 多年，无法使用现代 PHP 特性（typed properties、union types、enums、fibers 等）
2. Vue 2 已于 2023-12-31 EOL，不再接收安全补丁
3. Vue CLI 已停止维护，官方推荐迁移到 Vite
4. Silex 框架已废弃，不再维护；上游已提供 `oasis/http`（基于 `oasis/slimapp`）作为替代
5. PHPUnit 9 不支持 PHP 8.5 的新特性，且 PHPUnit 13 有大量 API 变更
6. 前端测试框架 Jest 在 Vue 3 + Vite 生态中不再是首选，Vitest 是当前主流

---

## Goals

- 将 PHP 最低版本要求升级到 8.5
- 将 PHPUnit 升级到 13+
- 将所有 Composer 依赖升级到最新大版本（允许破坏性变更）
- 将 Silex 替换为 `oasis/http`
- 将 Vue 2 迁移到 Vue 3
- 将构建工具从 Vue CLI (webpack) 迁移到 Vite
- 将前端测试从 Jest 迁移到 Vitest
- 将所有 npm 依赖升级到最新大版本（允许破坏性变更）
- 全面采用现代语法（PHP 8.5 特性、Vue 3 Composition API 等）
- 测试覆盖率（UT + PBT + Integration）> 90%
- 产出完整的迁移文档、迁移验证脚本、迁移脚本

---

## Non-Goals

- 不改变 SlimVue 的功能定位和核心架构（Bridge 机制、多页面入口、CLI 工具）
- 不改变目录结构的顶层组织方式
- 不引入新的功能特性（本次纯升级）
- 不考虑旧版本兼容——这是一次 breaking 升级

---

## Scope

### PHP 端

| 项目 | 变更内容 |
|------|----------|
| `composer.json` | PHP >= 8.5，所有依赖升级到最新大版本 |
| `src/*.php` | 适配新版依赖 API，全面采用 PHP 8.5 语法（typed properties、constructor promotion、union types、match 表达式、readonly 等） |
| `phpunit.xml` | 适配 PHPUnit 13 配置格式 |
| `tests/*.php` | 适配 PHPUnit 13 API，增加 PBT 测试 |
| `bin/slimvue` | 适配 Symfony Console 新版 API |
| `index.php` | Silex → `oasis/http` |

### 前端

| 项目 | 变更内容 |
|------|----------|
| `slimvue-template/package.json` | 所有依赖升级到最新大版本 |
| `slimvue-template/slimvue.js` | Vue 2 → Vue 3 API（`new Vue()` → `createApp()`） |
| `slimvue-template/src/components/*.vue` | Vue 3 `<script setup>` + Composition API |
| `slimvue-template/build/` | 移除，由 `vite.config.js` 替代 |
| `slimvue-template/vue.config.js` | 移除，替换为 `vite.config.js` |
| `slimvue-template/babel.config.js` | 移除（Vite 使用 esbuild） |
| `slimvue-template/tests/` | Jest → Vitest，增加 PBT 测试 |
| ESLint | 升级到最新大版本，迁移到 flat config |
| Prettier | 升级到最新大版本 |

### 迁移产出物

| 产出物 | 说明 |
|--------|------|
| 迁移文档 | 面向下游用户，记录所有 breaking changes 和手动迁移步骤 |
| 迁移验证脚本 | 面向下游用户，自动检查其项目的迁移完成度（版本号、API 用法、配置格式等） |
| 迁移脚本 | 面向下游用户，自动执行可自动化的迁移步骤（代码替换、配置转换等） |

---

## Upgrade Strategy

采用 red-green TDD 方式推进，PHP 端和前端各自遵循相同的阶段顺序：

### 阶段顺序

1. **升级依赖库**——修改版本约束，执行 install/update，更新项目文档；此阶段允许测试 fail
2. **升级测试用例**——适配新测试框架 API（PHPUnit 13 / Vitest），使测试重新 green
3. **升级语法**——全面采用新版语法特性（PHP 8.5 / Vue 3 + `<script setup>`），保持 green
4. **增加 PBT + 覆盖率**——引入 Property-Based Testing，目标覆盖率（UT + PBT + Integration）> 90%

### TDD 节奏

- 每个阶段内部以 red → green → refactor 循环推进
- 阶段 1 结束时允许 red（依赖升级导致的 breaking）
- 阶段 2 结束时必须 green（测试适配完成）
- 阶段 3 结束时必须 green（语法升级不引入回归）
- 阶段 4 结束时必须 green 且覆盖率达标

### 执行顺序

PHP 端先行，前端跟进。两端各自独立走完 4 个阶段。

---

## Technical Decisions

| 决策点 | 选择 | 理由 |
|--------|------|------|
| Silex 替代 | `oasis/http` | 上游已提供兼容方案，`oasis/slimapp` 已升级 |
| 前端构建工具 | Vite | Vue 官方推荐，生态主流，开发体验显著优于 webpack |
| 前端测试框架 | Vitest | 与 Vite 原生集成，API 兼容 Jest，Vue 3 生态首选 |
| Vue 3 组件风格 | `<script setup>` + Composition API | Vue 3 推荐写法，更好的 TypeScript 支持和 tree-shaking |
| core-js | 移除 | Vite 面向现代浏览器，不再需要 polyfill |
| babel | 移除 | Vite 使用 esbuild 转译，不再需要 Babel |

原则：**紧跟潮流，但不赶时髦**——选择已被广泛采用的成熟方案，不追实验性特性。

---

## Risks

- 前端构建配置（多页面入口、`BUILD_FILE_TYPE` 环境变量等）在 Vite 下需要重新实现
- 已有用户项目（通过 `initialize` 创建的）结构各异，迁移脚本需要处理用户自定义的组件、依赖和配置
- PBT 引入：JS 端 `fast-check` 成熟可靠；PHP 端 PBT 生态较弱，需在 spec 阶段确认合适的库（`eris` 或替代方案），如无合适选项可能需要降低 PHP 端 PBT 预期
- Vue 3 的全局 API 变更（`Vue.prototype` → `app.config.globalProperties`）影响 `slimvue.js` 的 bridge 注入方式

---

## Open Questions

1. PHP 端 PBT 库选型：需在 spec design 阶段确认可用的、维护活跃的 PHP PBT 库

---

## References

- `docs/state/architecture.md`
- `docs/state/data-model.md`
- `composer.json`
- `slimvue-template/package.json`
