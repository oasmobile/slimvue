# Changelog v4.0

本文件记录 v4.0 release 的变更内容。

---

## 包含的 Feature

### Full Stack Upgrade（PRP-001）

全栈 breaking 升级，覆盖 PHP 端和前端体系的大版本迁移。

#### PHP 端

- PHP 最低版本要求从 >= 7.0 升级到 >= 8.5
- Symfony Console 从 ^4.0 升级到 ^8.0
- Symfony Filesystem 从 ^4.0 升级到 ^8.0
- oasis/utils 从 ^1.7 升级到 ^3.0
- 移除 oasis/flysystem-wrappers 依赖
- Silex 替换为 oasis/http（开发依赖）
- Twig 从 ^1.0 升级到 ^3.0
- PHPUnit 从 ^9.0 升级到 ^13.0
- 新增 eris ~1.1（Property-Based Testing）
- 所有 PHP 源码采用 PHP 8.5 语法（typed properties、constructor promotion、match 表达式、readonly）
- SlimVueBridgeInterface 添加完整类型声明
- index.php 从 Silex 迁移到 oasis/http（MicroKernel），新增多路由、多数据类型演示、错误处理演示

#### 前端

- Vue.js 从 ^2.6 升级到 ^3.5
- 构建工具从 Vue CLI 4 (webpack) 迁移到 Vite 7
- 测试框架从 Jest 迁移到 Vitest
- ESLint 迁移到 flat config（eslint.config.js）
- Prettier 升级到 ^3.6
- Sass 升级到 ^1.92（移除 sass-loader）
- Node.js 最低版本要求 >= 24
- slimvue.js 迁移到 Vue 3 API（createApp、globalProperties）
- 所有模板组件重写为 `<script setup>` + Composition API
- 引入 composables 模式（useClock）
- 构建辅助模块从 build/ 迁移到 scripts/（ESM）
- 新增 Node.js 版本检查（scripts/check-node.js）
- 移除 core-js、Babel、vue-template-compiler 等过时依赖

#### CLI 工具

- bin/slimvue 版本号升至 4.0
- initialize 命令适配 Vite 模板结构，排除过时文件
- upgrade 命令新增字段缺失检测（name/version/dependencies/devDependencies 缺失时中止报错）
- upgrade 命令新增过时文件清理（build/、vue.config.js、babel.config.js、jest.config.js、.eslintrc.js）

#### 迁移产出物

- 迁移文档（docs/manual/migration-v4.md）：完整的 v3→v4 breaking changes 和手动迁移步骤
- 迁移验证脚本（bin/slimvue-migrate-check）：自动检查项目迁移完成度
- 迁移脚本（bin/slimvue-migrate）：自动执行可自动化的迁移步骤（含 AST 转换）

---

## 修复的 Issue

_无_

---

## 工程变更

- .gitignore 添加 slimvue-template/coverage/，从 git 跟踪中移除覆盖率产物
- .gitignore 添加 .vscode/ 和 web/
- PHP 命令约定从 php74 alias 改为直接使用 php

---

## 测试覆盖

- PHP：169 tests，1965 assertions，行覆盖率 > 90%（含 PBT Property 1–7）
- 前端：118 tests，行覆盖率 94.17%（含 PBT Property 8–12）
- 迁移工具：完整单元测试 + PBT（Property 13–19）
