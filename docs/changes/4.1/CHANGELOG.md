# Changelog v4.1

本文件记录 v4.1 release 的变更内容。

---

## 包含的 Feature

### Frontend Toolchain Upgrade（PRP-002）

前端构建与开发工具链大版本升级，不引入新功能特性，不涉及 PHP 端变更。

#### 构建链升级

- Vite ^7 → ^8（Rolldown 统一打包器取代 esbuild + Rollup 双引擎）
- Vitest ^3 → ^4
- @vitest/coverage-v8 ^3 → ^4

#### 构建链配置适配

- `vite.config.js`：`build.rollupOptions` 重命名为 `build.rolldownOptions`（Vite 8 breaking change）
- `vite.config.js`：`test.coverage` 添加显式 `include` 声明（Vitest 4 推荐写法）

#### Lint 链升级

- ESLint ^9 → ^10

#### Lint 链配置适配

- `eslint.config.js`：为各配置块添加 `name` 字段（ESLint 10 推荐写法）

#### 其余小版本依赖更新

- @vitejs/plugin-vue：最新 ^6 小版本
- eslint-config-prettier：最新 ^10 小版本
- eslint-plugin-vue：最新 ^10 小版本
- @vue/test-utils：最新 ^2 小版本
- prettier：最新 ^3 小版本
- jsdom：最新 ^29 小版本
- fast-check：最新 ^4 小版本
- sass：最新 ^1 小版本
- autoprefixer：最新 ^10 小版本

#### Composer 依赖更新

- PHP 依赖更新到约束范围内的最新版本

---

## 修复的 Issue

_无_

---

## 工程变更

- Lock 文件删除后重新生成（干净的依赖树）

---

## 文档变更

- `docs/state/architecture.md`：Vite 版本 ^7 → ^8，描述更新为 Rolldown
- `docs/state/architecture.md`：Vitest 版本 ^3 → ^4
- `docs/state/architecture.md`：ESLint 版本 ^9 → ^10，格式更新为 ^10 / ^3

---

## 测试覆盖

- PHPUnit：169 tests, 1983 assertions — 全部通过
- PHPStan level 8：0 errors
- ESLint：0 errors
- Vitest：118 tests — 全部通过
