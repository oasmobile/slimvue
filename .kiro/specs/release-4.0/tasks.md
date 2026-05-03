# Implementation Plan: Full Stack Upgrade (v4.0)

## Overview

基于 requirements.md（16 条 Requirement）和 design.md 的技术方案，将升级工作拆分为可执行的编码任务。执行策略：PHP 端先行（4 阶段），前端跟进（4 阶段），迁移产出物最后。每端遵循 red-green TDD 节奏：阶段 1 允许 red，阶段 2 结束必须 green，阶段 3-4 保持 green。

构建辅助模块从 `build/` 移到 `scripts/` 目录（Design Gatekeep Q1 决策）。迁移工具作为独立 task，在 PHP 端和前端升级全部完成后执行（Design Gatekeep Q2 决策）。AST 转换测试放在 `slimvue-template/tests/`（Design Gatekeep Q4 决策）。

## Tasks

### PHP 端 — 阶段 1: 升级依赖（允许 red）

- [x] 1. 升级 PHP 依赖与仓库清理
  - [x] 1.1 更新 `composer.json` 依赖声明
    - 将 `require.php` 从 `>=7.0` 改为 `>=8.5`
    - 将 `symfony/console` 从 `^4.0` 升级到 `^8.0`
    - 将 `symfony/filesystem` 从 `^4.0` 升级到 `^8.0`
    - 将 `oasis/utils` 从 `^1.7` 升级到最新大版本
    - 将 `oasis/flysystem-wrappers` 从 `^1.3` 升级到最新大版本
    - 在 `require-dev` 中将 `silex/silex` 替换为 `oasis/http`
    - 在 `require-dev` 中将 `twig/twig` 从 `^1.0` 升级到最新大版本
    - 在 `require-dev` 中将 `phpunit/phpunit` 从 `^9.0` 升级到 `^13.0`
    - 在 `require-dev` 中新增 `giorgiosironi/eris` `~1.1`
    - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5, 1.6, 1.7, 1.8, 1.9_
  - [x] 1.2 执行 `composer install` 并解决依赖冲突
    - 运行 `php $(which composer) install`，确保无依赖解析错误
    - 如有冲突，调整版本约束直到安装成功
    - _Requirements: 1.10_
  - [x] 1.3 清理覆盖率产物与 `.gitignore`
    - 确认 `.gitignore` 已包含 `slimvue-template/coverage/`（当前已有）
    - 执行 `git rm -r --cached slimvue-template/coverage/`（如仍被 git 跟踪）
    - _Requirements: 13.1, 13.2_
  - [x] 1.4 更新 `PROJECT.md` 中的 PHP 命令约定
    - 将所有 `php74` 引用替换为 `php`（Gatekeep Q3 决策）
    - 更新技术栈表格中的版本信息
    - _Requirements: Gatekeep Q3_
  - [x] 1.5 Checkpoint — 确认依赖安装成功
    - 运行 `php $(which composer) install`，确认无错误
    - 此阶段允许测试 red（PHPUnit 13 API 不兼容）
    - 通过后 commit
    - 如有问题请向用户确认

### PHP 端 — 阶段 2: 升级测试（结束时必须 green）

- [x] 2. PHPUnit 13 适配与测试修复
  - [x] 2.1 编写 PHPUnit 13 兼容测试（RED）
    - 更新 `phpunit.xml`：移除 PHPUnit 13 不支持的属性（`verbose`、`forceCoversAnnotation`、`beStrictAboutCoversAnnotation`、`beStrictAboutTodoAnnotatedTests`），适配 PHPUnit 13 配置 schema
    - 更新 `tests/TwigBridgeInfoTest.php`：适配 PHPUnit 13 API（deprecated assertion methods、annotations）
    - 更新 `tests/SlimVueInitializeCommandTest.php`：适配 PHPUnit 13 API
    - 更新 `tests/SlimVueUpgradeCommandTest.php`：适配 PHPUnit 13 API
    - 在所有测试文件中采用 PHP 8.5 语法（typed properties、constructor promotion、return types）
    - 此时测试可能因源码尚未升级而 fail
    - _Requirements: 3.1, 3.2, 3.3_
  - [x] 2.2 修复源码使测试通过（GREEN）
    - 根据测试失败信息，对 `src/*.php` 做最小修改使测试通过
    - 重点关注 Symfony Console ^8 的 API 变更（`Command::execute()` 返回类型等）
    - 运行 `php vendor/bin/phpunit`，确认全部通过
    - _Requirements: 3.4_
  - [x] 2.3 Checkpoint — PHPUnit 13 测试全部通过
    - 运行 `php vendor/bin/phpunit`，确认 zero failures、zero errors
    - 确认输出无 deprecation warning
    - 通过后 commit
    - 如有问题请向用户确认

### PHP 端 — 阶段 3: 升级语法（保持 green）

- [x] 3. PHP 源码现代化
  - [x] 3.1 升级 `SlimVueBridgeInterface`
    - 为所有方法参数和返回值添加类型声明（`string`、`mixed`、`void`）
    - 按 design.md 中的接口签名实现
    - 移除冗余 PHPDoc
    - _Requirements: 2.3, 2.7_
  - [x] 3.2 升级 `TwigBridgeInfo`
    - 使用 constructor promotion（`private array $data`）
    - 添加 typed properties、typed parameters、return types
    - 使用 `match` 表达式替代 `if-else`（`getPlainValue` 方法）
    - 使用 `readonly` 属性（如适用）
    - 移除冗余 PHPDoc
    - 运行 `php vendor/bin/phpunit` 确认 green
    - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5, 2.7_
  - [x] 3.3 升级 `SlimVueInitializeCommand`
    - 使用 constructor promotion、typed properties
    - 添加 return types、parameter types
    - 更新输出消息：`npm run serve` → `npm run dev`
    - 更新 `templateIterator()` 排除列表：排除 `build/`、`vue.config.js`、`babel.config.js`、`jest.config.js`
    - 使用 `readonly`、named arguments（如适用）
    - 移除冗余 PHPDoc
    - 运行 `php vendor/bin/phpunit` 确认 green
    - _Requirements: 2.1, 2.2, 2.3, 2.5, 2.6, 2.7, 12.2, 12.3, 12.5_
  - [x] 3.4 升级 `SlimVueUpgradeCommand`
    - 使用 constructor promotion、typed properties
    - 添加 return types、parameter types
    - 新增字段缺失检测逻辑：当 `package.json` 缺少 `name`、`version`、`dependencies`、`devDependencies` 时中止并报错（Gatekeep Q1 决策）
    - 升级过程中移除目标目录中的过时文件（`build/`、`vue.config.js`、`babel.config.js`、`jest.config.js`、`.eslintrc.js`）
    - 使用 `readonly`、named arguments（如适用）
    - 移除冗余 PHPDoc
    - 运行 `php vendor/bin/phpunit` 确认 green
    - _Requirements: 2.1, 2.2, 2.3, 2.5, 2.6, 2.7, 12.4, 12.5, 12.7_
  - [x] 3.5 升级 `bin/slimvue` CLI 入口
    - 适配 Symfony Console ^8 API
    - 确认版本号为 `4.0`
    - _Requirements: 12.1_
  - [x] 3.6 实现 `index.php` 替换（Silex → oasis/http）
    - 按 design.md 中的具体设计实现：
      - 创建 `index.php`：使用 `Oasis\Mlib\Http\MicroKernel`，配置 routing、twig、error_handlers
      - 创建 `routes.yml`：定义 home、subpage、error_demo 三个路由
      - 创建 `demo/DemoController.php`：实现 `homeAction`（多种 bridge 数据类型演示 + `getExecTwig` 演示）、`subpageAction`、`errorDemoAction`（应用层错误处理）
      - 创建 `demo/DemoErrorHandler.php`：框架级错误处理（404/500）
    - 在 `composer.json` 的 `autoload-dev` 中添加 demo 命名空间
    - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5, 5.6_
  - [x] 3.7 补充 `SlimVueUpgradeCommand` 字段缺失检测的单元测试
    - 测试 `package.json` 缺少 `name` 时中止并报错
    - 测试 `package.json` 缺少 `version` 时中止并报错
    - 测试 `package.json` 缺少 `dependencies` 时中止并报错
    - 测试 `package.json` 缺少 `devDependencies` 时中止并报错
    - 运行 `php vendor/bin/phpunit` 确认 green
    - _Requirements: 12.4, Gatekeep Q1_
  - [x] 3.8 Checkpoint — PHP 源码现代化完成
    - 运行 `php vendor/bin/phpunit`，确认全部通过
    - 确认输出无 deprecation warning
    - 通过后 commit
    - 如有问题请向用户确认

### PHP 端 — 阶段 4: PBT + 覆盖率（保持 green，覆盖率 > 90%）

- [x] 4. PHP Property-Based Testing
  - [x] 4.1 为 `TwigBridgeInfo` 编写 PBT（Property 1–3）
    - 在 `tests/TwigBridgeInfoTest.php` 中新增 PBT 测试方法
    - **Property 1: render round-trip** — 对任意合法 bridge 数据，`json_decode(render(), true)` 应与原始输入等价
    - **Validates: Requirements 4.1**
    - **Property 2: add() idempotence** — 对同一 key-value 执行 `add()` 两次，`render()` 输出应与执行一次相同
    - **Validates: Requirements 4.2**
    - **Property 3: getExecTwig() metamorphic length** — 输出长度与输入长度之差：以 `slimvue/pages/` 开头时为 6，否则为 0
    - **Validates: Requirements 4.3**
    - 运行 `php vendor/bin/phpunit` 确认 green
    - _Requirements: 4.1, 4.2, 4.3_
  - [x] 4.2 为 `SlimVueInitializeCommand` 编写 PBT（Property 4, 6）
    - 在 `tests/SlimVueInitializeCommandTest.php` 中新增 PBT 测试方法
    - **Property 4: name+version invariant** — 对任意合法项目名称，生成的 `package.json` 应包含该名称且 version 为 `0.1.0`
    - **Validates: Requirements 4.4**
    - **Property 6: excludes obsolete files** — 对任意合法项目名称，生成的项目目录不应包含 `build/`、`vue.config.js`、`babel.config.js`、`jest.config.js`
    - **Validates: Requirements 12.2**
    - 运行 `php vendor/bin/phpunit` 确认 green
    - _Requirements: 4.4, 12.2_
  - [x] 4.3 为 `SlimVueUpgradeCommand` 编写 PBT（Property 5, 7）
    - 在 `tests/SlimVueUpgradeCommandTest.php` 中新增 PBT 测试方法
    - **Property 5: name+version preservation** — 对任意已存在项目，upgrade 后 `package.json` 的 name 和 version 应与升级前一致
    - **Validates: Requirements 4.5**
    - **Property 7: removes obsolete files** — 对任意包含过时文件的项目，upgrade 后这些文件应被移除
    - **Validates: Requirements 12.4**
    - 运行 `php vendor/bin/phpunit` 确认 green
    - _Requirements: 4.5, 12.4_
  - [x] 4.4 PHP 覆盖率验证
    - 运行 `php vendor/bin/phpunit --coverage-text`
    - 确认 `src/` 下所有文件的行覆盖率 > 90%
    - 如覆盖率不足，补充单元测试直到达标
    - _Requirements: 4.6, 4.7, 13.3, 13.5_
  - [x] 4.5 Checkpoint — PHP 端全部完成
    - 运行 `php vendor/bin/phpunit`，确认全部通过
    - 运行 `php vendor/bin/phpunit --coverage-text`，确认覆盖率 > 90%
    - 通过后 commit
    - 如有问题请向用户确认

### 前端 — 阶段 1: 升级依赖（允许 red）

- [x] 5. 升级前端依赖
  - [x] 5.1 更新 `slimvue-template/package.json` 依赖声明
    - 将 `vue` 从 `^2.6.11` 升级到 `^3`
    - 移除 `core-js`
    - 移除所有 Vue CLI 相关包（`@vue/cli-service`、`@vue/cli-plugin-babel`、`@vue/cli-plugin-eslint`）
    - 移除所有 Babel 相关包（`babel-core`、`babel-eslint`、`babel-jest`）
    - 移除 `vue-template-compiler`、`vue-jest`、`sass-loader`
    - 新增 `vite` 和 `@vitejs/plugin-vue`（最新大版本）
    - 新增 `vitest`、`@vue/test-utils` `^2`（Vue 3 兼容）、`fast-check`（最新大版本）
    - 新增 `eslint`（最新大版本，flat config 支持）、`eslint-plugin-vue`（Vue 3 compatible）、`eslint-config-prettier`
    - 升级 `prettier` 到最新大版本
    - 升级 `sass` 到最新大版本
    - 添加 `"engines": { "node": ">=24" }`
    - 更新 `scripts`：按 design.md Data Models 中的 scripts 变更表更新（`dev`、`build`、`release`、`preview`、`lint`、`test`、`test:watch`、`test:coverage`）
    - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.5, 6.6, 6.7, 6.8, 6.9, 6.10, 6.11, 7.10, 9.4_
  - [x] 5.2 执行 `npm install` 并解决依赖冲突
    - 在 `slimvue-template/` 下运行 `npm install`
    - 如有冲突，调整版本约束直到安装成功
    - _Requirements: 6.13_
  - [x] 5.3 移除过时配置文件
    - 删除 `slimvue-template/vue.config.js`
    - 删除 `slimvue-template/babel.config.js`
    - 删除 `slimvue-template/jest.config.js`
    - 删除 `slimvue-template/.eslintrc.js`
    - 删除整个 `slimvue-template/build/` 目录
    - _Requirements: 7.9_
  - [x] 5.4 创建 `scripts/` 目录与构建辅助模块
    - 创建 `slimvue-template/scripts/entries.js`（ESM）：从 `build/entries.js` 迁移，实现多页面入口扫描（保持相同的扫描逻辑和命名约定）
    - 创建 `slimvue-template/scripts/check-node.js`（ESM）：Node.js 版本检查，同时支持独立脚本执行和模块导入
    - 创建 `slimvue-template/scripts/tdk.js`（ESM）：TDK 元数据注入 Vite 插件（`transformIndexHtml` 钩子）
    - _Requirements: 6.12, 7.2, 7.8, 7.11, Design Gatekeep Q1_
  - [x] 5.5 创建 `vite.config.js`
    - 按 design.md 中的配置结构实现
    - 集成 `@vitejs/plugin-vue`
    - 集成 `scripts/entries.js` 多页面入口扫描
    - 集成 `scripts/tdk.js` TDK 插件
    - 调用 `scripts/check-node.js` 的 `checkNodeVersion(24)`
    - 支持 `BUILD_FILE_TYPE`、`PUBLIC_PATH`、`OUTPUT_DIR`、`EXCLUED_ENTRIES` 环境变量
    - 配置 resolve alias（`@`、`slimvue`、`assets`）
    - _Requirements: 7.1, 7.2, 7.3, 7.4, 7.5, 7.6, 7.7, 7.8_
  - [x] 5.6 Checkpoint — 前端依赖安装成功
    - 在 `slimvue-template/` 下运行 `npm install`，确认无错误
    - 此阶段允许测试 red（Vitest 尚未配置、源码尚未迁移）
    - 通过后 commit
    - 如有问题请向用户确认

### 前端 — 阶段 2: 升级测试（结束时必须 green）

- [x] 6. Jest → Vitest 迁移与测试修复
  - [x] 6.1 编写 Vitest 配置（RED）
    - 在 `vite.config.js` 中添加 Vitest 配置（或创建独立 `vitest.config.js`）
    - 配置测试环境（jsdom）、覆盖率工具
    - _Requirements: 9.1_
  - [x] 6.2 迁移测试文件到 Vitest API（RED）
    - 更新 `tests/slimvue.test.js`：`jest.fn()` → `vi.fn()`、`jest.spyOn()` → `vi.spyOn()` 等
    - 更新 `tests/build-entries.test.js`：适配 ESM 导入和 Vitest API
    - 更新 `tests/build-config.test.js`：适配 Vite 配置结构
    - 更新 `tests/build-tdk.test.js`：适配 Vite `transformIndexHtml` 插件
    - 更新 `tests/components.test.js`：适配 `@vue/test-utils` ^2（Vue 3 兼容）
    - 移除或重写不再适用的测试文件（`build-devServer.test.js`、`build-resolve.test.js`、`build-transformAssetUrls.test.js`、`build-webpack-add.test.js`、`build-webpack-modify.test.js`）
    - 此时测试可能因源码尚未迁移而 fail
    - _Requirements: 9.2, 9.3_
  - [x] 6.3 迁移 `slimvue.js` 到 Vue 3 API（GREEN）
    - 按 design.md 中的 `slimvue.js` 设计实现：
      - `import Vue from 'vue'` → `import { createApp } from 'vue'`
      - `new Vue().$mount()` → `createApp().mount()`
      - `Vue.prototype.$xxx` → `app.config.globalProperties.$xxx`（移入 `mount()` 内部）
      - `Vue.config.productionTip` → 移除
      - `process.env.NODE_ENV` → `import.meta.env.MODE`
      - `process.env.VUE_APP_BRIDGE` → `import.meta.env.VITE_BRIDGE`
    - 运行 `npx vitest run` 确认 `slimvue.test.js` 通过
    - _Requirements: 8.1_
  - [x] 6.4 迁移构建配置测试（GREEN）
    - 确保 `tests/build-entries.test.js` 测试 `scripts/entries.js` 的入口扫描逻辑
    - 确保 `tests/build-tdk.test.js` 测试 `scripts/tdk.js` 的 TDK 注入逻辑
    - 确保 `tests/build-config.test.js` 测试 `vite.config.js` 的配置结构
    - 运行 `npx vitest run` 确认通过
    - _Requirements: 9.5_
  - [x] 6.5 Checkpoint — Vitest 测试全部通过
    - 在 `slimvue-template/` 下运行 `npx vitest run`，确认 zero failures
    - 确认输出无 warning
    - 通过后 commit
    - 如有问题请向用户确认

### 前端 — 阶段 3: 升级语法与组件（保持 green）

- [x] 7. Vue 3 组件重新设计与 ESLint/Prettier 升级
  - [x] 7.1 重新设计 Vue 3 模板组件
    - 按 design.md 中的组件设计实现：
      - 重写 `App.vue`：使用 `<script setup>`、`computed`、bridge 数据展示
      - 重写 `HelloWorld.vue`：使用 `defineProps`、`defineEmits`
      - 重写 `MyClock.vue`：使用 `ref`、`computed`、`watch`、composables
      - 重写 `SubPage.vue`：使用 `reactive`
      - 创建 `src/composables/useClock.js`：提取可复用逻辑
    - 更新所有 entry 文件（`src/entries/*.js`）使用新的 `slimvue.mount()` API
    - 更新 `template/index.html` 和 `template/index.twig` 兼容 Vite HTML 入口机制
    - 运行 `npx vitest run` 确认 green
    - _Requirements: 8.2, 8.3, 8.4, 8.5, 8.6, 8.7, 8.8_
  - [x] 7.2 更新组件测试
    - 更新 `tests/components.test.js`：适配 Vue 3 组件（`<script setup>`、Composition API）
    - 使用 `@vue/test-utils` ^2 的 `mount`/`shallowMount` API
    - 运行 `npx vitest run` 确认 green
    - _Requirements: 9.5_
  - [x] 7.3 创建 ESLint flat config
    - 创建 `slimvue-template/eslint.config.js`：按 design.md 中的 flat config 结构实现
    - 使用 `eslint-plugin-vue` 的 Vue 3 recommended config（`flat/recommended`）
    - 集成 `eslint-config-prettier` 关闭冲突规则
    - _Requirements: 11.1, 11.2, 11.3_
  - [x] 7.4 更新 Prettier 配置
    - 更新 `slimvue-template/prettier.config.js`：适配 Prettier 最新大版本选项
    - _Requirements: 11.4_
  - [x] 7.5 运行 lint 并修复
    - 在 `slimvue-template/` 下运行 `npm run lint`，修复所有 lint 错误
    - _Requirements: 11.5_
  - [x] 7.6 Checkpoint — 前端语法升级完成
    - 运行 `npx vitest run`，确认全部通过
    - 运行 `npm run lint`，确认 zero errors
    - 通过后 commit
    - 如有问题请向用户确认

### 前端 — 阶段 4: PBT + 覆盖率（保持 green，覆盖率 > 90%）

- [-] 8. 前端 Property-Based Testing
  - [x] 8.1 为 `slimvue.js` 编写 PBT（Property 8–9）
    - 在 `tests/slimvue.test.js` 中新增 PBT 测试
    - **Property 8: bridge getter round-trip** — 对任意合法 JSON-serializable 对象，赋值给 `window.bridge` 后通过 getter 读取应返回等价对象
    - **Validates: Requirements 10.1**
    - **Property 9: logLevel setter/getter round-trip** — 对任意有效日志级别数值，setter 后 getter 应返回相同值
    - **Validates: Requirements 10.2**
    - 运行 `npx vitest run` 确认 green
    - _Requirements: 10.1, 10.2_
  - [x] 8.2 为入口扫描器编写 PBT（Property 10–11）
    - 在 `tests/build-entries.test.js` 中新增 PBT 测试
    - **Property 10: entry scanner correctness** — 对任意入口文件集合和排除列表，扫描器应为每个未排除文件生成恰好一个页面配置，输出路径遵循命名约定
    - **Validates: Requirements 7.2, 7.6, 7.7**
    - **Property 11: add entry increases page count** — 添加一个新 `.js` 文件后，页面数量应恰好增加 1
    - **Validates: Requirements 10.3**
    - 运行 `npx vitest run` 确认 green
    - _Requirements: 10.3_
  - [x] 8.3 为 TDK 注入编写 PBT（Property 12）
    - 在 `tests/build-tdk.test.js` 中新增 PBT 测试
    - **Property 12: TDK metadata injection invariant** — 对任意合法 TDK 对象，注入后的 HTML 应包含指定的 title、keywords、description
    - **Validates: Requirements 7.8, 10.4**
    - 运行 `npx vitest run` 确认 green
    - _Requirements: 10.4_
  - [x] 8.4 前端覆盖率验证
    - 运行 `npx vitest run --coverage`
    - 确认 `slimvue.js`、`scripts/` 构建模块、`src/components/` 的行覆盖率 > 90%
    - 如覆盖率不足，补充单元测试直到达标
    - _Requirements: 10.5, 10.6, 13.4, 13.6_
  - [-] 8.5 Checkpoint — 前端全部完成
    - 运行 `npx vitest run`，确认全部通过
    - 运行 `npx vitest run --coverage`，确认覆盖率 > 90%
    - 运行 `npm run lint`，确认 zero errors
    - 通过后 commit
    - 如有问题请向用户确认

### 迁移产出物（PHP 端和前端升级全部完成后）

- [ ] 9. 迁移文档
  - [ ] 9.1 编写迁移文档 `docs/changes/4.0/MIGRATION.md`
    - 列出所有 PHP breaking changes（PHP 版本、依赖替换、Symfony Console/Filesystem API 变更、Twig 版本变更、oasis/utils 和 oasis/flysystem-wrappers 变更）
    - 列出所有前端 breaking changes（Vue 2→3 API 变更、webpack→Vite 配置变更、Jest→Vitest 迁移、ESLint flat config 迁移）
    - 列出所有 `slimvue.js` API 变更（`Vue.prototype` → `globalProperties`、`new Vue()` → `createApp()`、mount 行为变更）
    - 列出所有 CLI 命令行为变更（新模板结构、移除文件、更新输出消息）
    - 提供每个 breaking change 的逐步手动迁移指南
    - _Requirements: 14.1, 14.2, 14.3, 14.4, 14.5, 14.6_
  - [ ] 9.2 Checkpoint — 迁移文档完成
    - Review 文档完整性：确认覆盖所有 breaking changes
    - 通过后 commit
    - 如有问题请向用户确认

- [ ] 10. 迁移验证脚本
  - [ ] 10.1 实现迁移验证脚本 `bin/slimvue-migrate-check`
    - 创建独立 PHP 脚本，接受项目目录作为参数
    - 实现检查项：PHP 版本约束、废弃依赖引用、PHP 废弃 API 模式、JS/Vue 废弃 API 模式、已移除文件检测
    - 输出格式：每项检查显示 ✓/✗ 状态 + 修复建议
    - 输出汇总报告（total/passed/failed）
    - _Requirements: 15.1, 15.2, 15.3, 15.4, 15.5, 15.6, 15.7_
  - [ ] 10.2 为迁移验证脚本编写单元测试
    - 在 `tests/MigrationValidatorTest.php` 中编写测试
    - 覆盖各检查项的 pass/fail 场景
    - 运行 `php vendor/bin/phpunit` 确认 green
    - _Requirements: 15.1–15.6_
  - [ ] 10.3 为迁移验证脚本编写 PBT（Property 13–15）
    - 在 `tests/MigrationValidatorTest.php` 中新增 PBT 测试
    - **Property 13: dependency/version validation** — 对任意 `composer.json`，正确识别 PHP 版本约束和废弃依赖
    - **Validates: Requirements 15.1, 15.2**
    - **Property 14: deprecated pattern detection** — 对任意源文件内容，正确检测废弃 API 模式且不产生误报
    - **Validates: Requirements 15.3, 15.4**
    - **Property 15: removed files detection** — 对任意项目目录，正确识别应被移除的文件
    - **Validates: Requirements 15.5**
    - 运行 `php vendor/bin/phpunit` 确认 green
    - _Requirements: 15.1–15.5_
  - [ ] 10.4 Checkpoint — 迁移验证脚本完成
    - 运行 `php vendor/bin/phpunit`，确认全部通过
    - 通过后 commit
    - 如有问题请向用户确认

- [ ] 11. 迁移脚本
  - [ ] 11.1 实现迁移脚本 `bin/slimvue-migrate`
    - 创建独立 PHP 脚本，接受项目目录作为参数
    - 实现备份机制：对所有将修改的文件创建 `.bak` 备份
    - 实现 `composer.json` 更新：PHP 版本约束 → >= 8.5，替换废弃依赖
    - 实现过时文件移除：`vue.config.js`、`babel.config.js`、`jest.config.js`、`.eslintrc.js`、`build/`
    - 实现 `package.json` scripts 更新：Vue CLI 命令 → Vite 命令
    - 输出变更日志：列出所有已执行的变更和需要手动处理的项目
    - _Requirements: 16.1, 16.2, 16.3, 16.5, 16.6, 16.7, 16.8_
  - [ ] 11.2 实现 AST 转换脚本 `bin/transforms/vue2-to-vue3.js`
    - 创建 jscodeshift transform 脚本
    - 实现 `new Vue(` → `createApp(` 转换（仅实际代码，不影响注释和字符串）
    - 实现 `Vue.prototype` → `app.config.globalProperties` 转换
    - 保持代码语法正确性
    - 在迁移脚本中通过 `exec()` 调用此 transform
    - 如目标环境无 Node.js，回退到正则替换并在日志中警告
    - _Requirements: 16.4, Gatekeep Q2_
  - [ ] 11.3 为迁移脚本编写单元测试
    - 在 `tests/MigrationScriptTest.php` 中编写测试
    - 覆盖各步骤的正常和异常场景（备份、配置更新、文件移除、scripts 更新）
    - 运行 `php vendor/bin/phpunit` 确认 green
    - _Requirements: 16.1–16.7_
  - [ ] 11.4 为 AST 转换编写测试（Property 18）
    - 在 `slimvue-template/tests/migration-transform.test.js` 中编写测试（Design Gatekeep Q4 决策）
    - **Property 18: AST-based JS code transformation** — 对任意包含 Vue 2 API 调用的 JS 文件，正确替换实际代码中的调用，不修改注释和字符串，保持语法正确性
    - **Validates: Requirements 16.4**
    - 运行 `npx vitest run` 确认 green
    - _Requirements: 16.4_
  - [ ] 11.5 为迁移脚本编写 PBT（Property 16–17, 19）
    - 在 `tests/MigrationScriptTest.php` 中新增 PBT 测试
    - **Property 16: config file migration** — 对任意 `composer.json` 和 `package.json`，正确更新版本约束、替换依赖、更新 scripts
    - **Validates: Requirements 16.1, 16.2, 16.5**
    - **Property 17: obsolete file removal** — 对任意项目目录，正确移除所有指定过时文件且不影响其他文件
    - **Validates: Requirements 16.3**
    - **Property 19: backup creation** — 对任意将被修改的文件集合，修改前创建 `.bak` 备份且内容一致
    - **Validates: Requirements 16.6**
    - 运行 `php vendor/bin/phpunit` 确认 green
    - _Requirements: 16.1, 16.3, 16.6_
  - [ ] 11.6 Checkpoint — 迁移脚本完成
    - 运行 `php vendor/bin/phpunit`，确认全部通过
    - 在 `slimvue-template/` 下运行 `npx vitest run`，确认全部通过
    - 通过后 commit
    - 如有问题请向用户确认

### State 文档更新与最终验证

- [ ] 12. 更新 State 文档
  - [ ] 12.1 更新 `docs/state/architecture.md`
    - 更新技术选型表：PHP >= 8.5、Symfony ^8、Vue ^3、Vite、oasis/http
    - 更新 CLI 命令 — `upgrade` section：新增字段缺失检测行为
    - 更新多页面入口机制：webpack → Vite
    - 更新分层结构图
    - _Requirements: Impact Analysis_
  - [ ] 12.2 更新 `docs/state/data-model.md`
    - 更新 PHP 接口：所有方法添加类型声明
    - 更新前端核心模块：bridge getter 环境变量前缀、mount() API
    - 更新构建配置：环境变量前缀变更、Node.js 版本检查
    - _Requirements: Impact Analysis_
  - [ ] 12.3 更新 `PROJECT.md`
    - 更新技术栈表格：PHP 版本、依赖版本、前端框架版本
    - 更新构建与运行命令：`php74` → `php`、`npm run serve` → `npm run dev`、新增 Vitest 命令
    - 更新运行入口：`index.php` 从 Silex 改为 oasis/http
    - 更新目录结构概览：`build/` → `scripts/`、新增 `demo/`
    - _Requirements: Impact Analysis_
  - [ ] 12.4 Checkpoint — State 文档更新完成
    - Review 文档一致性：确认 state 文档与实际代码一致
    - 通过后 commit
    - 如有问题请向用户确认

### 手工测试与 Code Review

- [ ] 13. 手工测试
  - [ ] 13.1 Increment alpha tag
    - 查询已有 alpha tag，取最大序号 +1 打新 tag
  - [ ] 13.2 PHP 端集成验证
    - [脚本] 运行 `php $(which composer) install`，确认无错误
    - [脚本] 运行 `php vendor/bin/phpunit`，确认全部通过
    - [脚本] 运行 `php vendor/bin/phpunit --coverage-text`，确认覆盖率 > 90%
    - [脚本] 运行 `bin/slimvue initialize testproj --directory /tmp/slimvue-test`，确认生成有效的 Vite 项目
    - [脚本] 验证生成的项目不包含过时文件（`build/`、`vue.config.js` 等）
    - [脚本] 运行 `bin/slimvue upgrade /tmp/slimvue-test`，确认升级成功且保留 name/version
    - _Requirements: 1.10, 3.4, 4.6, 12.6, 12.7, 13.3, 13.5_
  - [ ] 13.3 前端集成验证
    - [脚本] 在 `slimvue-template/` 下运行 `npm install`，确认无错误
    - [脚本] 运行 `npx vitest run`，确认全部通过
    - [脚本] 运行 `npx vitest run --coverage`，确认覆盖率 > 90%
    - [脚本] 运行 `npm run build`，确认构建产出在 `dist/` 下且多页面结构正确
    - [脚本] 运行 `npm run lint`，确认 zero errors
    - _Requirements: 6.13, 7.12, 9.5, 10.5, 10.6, 11.5, 13.4, 13.6_
  - [ ] 13.4 迁移工具验证
    - [脚本] 创建模拟 v3 项目目录
    - [脚本] 运行 `bin/slimvue-migrate-check` 验证检测功能
    - [脚本] 运行 `bin/slimvue-migrate` 验证迁移功能
    - [脚本] 再次运行 `bin/slimvue-migrate-check` 确认迁移后全部通过
    - _Requirements: 15.7, 16.8_
  - [ ] 13.5 Checkpoint — 手工测试完成
    - 汇总所有测试结果
    - 通过后 commit
    - 如有问题请向用户确认

- [ ] 14. Code Review
  - 委托给 `code-reviewer` sub-agent 执行
  - 基于当前分支的 diff 进行全面 code review

## Notes

- 执行时须遵循 `.kiro/steering/spec/spec-execution.md` 中的规范
- Commit 随 checkpoint 一起执行——每个 checkpoint 通过后进行一次 commit
- PHP 命令使用 `php`（非 `php74`），与 Gatekeep Q3 决策一致
- 构建辅助模块位于 `slimvue-template/scripts/`（非 `build/`），与 Design Gatekeep Q1 决策一致
- 迁移工具作为独立 task（9–11），在 PHP 端和前端升级全部完成后执行，与 Design Gatekeep Q2 决策一致
- AST 转换测试位于 `slimvue-template/tests/`，与 Design Gatekeep Q4 决策一致
- TDD 节奏：阶段 1 允许 red，阶段 2 结束必须 green，阶段 3-4 保持 green
- 每个 top-level task 的最后一个 sub-task 为 Checkpoint
- 所有 tasks 均为 mandatory，无 optional 标记
- Property-Based Testing 标签格式：`Feature: release-4.0, Property {number}: {property_text}`

---

## Socratic Review Log

### Round 1 — Self Review

**Q1: 所有 16 条 Requirement 是否都有对应的 task 覆盖？**

逐条检查：
- Req 1（PHP 依赖升级）→ Task 1.1, 1.2 ✓
- Req 2（PHP 源码现代化）→ Task 3.1–3.5 ✓
- Req 3（PHPUnit 13 适配）→ Task 2.1–2.2 ✓
- Req 4（PHP PBT）→ Task 4.1–4.4 ✓
- Req 5（index.php 增强）→ Task 3.6 ✓
- Req 6（前端依赖升级）→ Task 5.1–5.2 ✓
- Req 7（Vite 迁移）→ Task 5.3–5.5 ✓
- Req 8（Vue 3 迁移）→ Task 6.3, 7.1 ✓
- Req 9（Vitest 迁移）→ Task 6.1–6.4 ✓
- Req 10（前端 PBT）→ Task 8.1–8.4 ✓
- Req 11（ESLint/Prettier 升级）→ Task 7.3–7.5 ✓
- Req 12（CLI 适配）→ Task 3.3–3.5, 3.7, 4.2–4.3 ✓
- Req 13（覆盖率与 CI 清理）→ Task 1.3, 4.4, 8.4 ✓
- Req 14（迁移文档）→ Task 9.1 ✓
- Req 15（迁移验证脚本）→ Task 10.1–10.3 ✓
- Req 16（迁移脚本）→ Task 11.1–11.5 ✓

**Q2: 所有 19 条 Correctness Properties 是否都有对应的 PBT task？**

- Property 1–3 → Task 4.1 ✓
- Property 4, 6 → Task 4.2 ✓
- Property 5, 7 → Task 4.3 ✓
- Property 8–9 → Task 8.1 ✓
- Property 10–11 → Task 8.2 ✓
- Property 12 → Task 8.3 ✓
- Property 13–15 → Task 10.3 ✓
- Property 16–17, 19 → Task 11.5 ✓
- Property 18 → Task 11.4 ✓

**Q3: Design Gatekeep 的 4 项决策是否都已体现？**

- Q1（构建辅助模块移到 `scripts/`）→ Task 5.3, 5.4, Notes ✓
- Q2（迁移工具独立 task）→ Task 9–11 在 PHP/前端之后，Notes ✓
- Q3（先调研 oasis/http 再生成 tasks）→ design.md 已更新为具体代码 ✓
- Q4（AST 转换测试放 `slimvue-template/tests/`）→ Task 11.4, Notes ✓

**Q4: Requirements Gatekeep 的 4 项决策是否都已体现？**

- Q1（upgrade 缺少字段时中止报错）→ Task 3.4, 3.7 ✓
- Q2（迁移脚本使用 AST 解析）→ Task 11.2 ✓
- Q3（移除 php74 alias）→ Task 1.4, Notes ✓
- Q4（index.php 同时演示应用层和框架级错误处理）→ Task 3.6 ✓

**Q5: 用户关键约束是否都已满足？**

- 执行顺序：PHP 先行（Task 1–4），前端跟进（Task 5–8）✓
- TDD 节奏：阶段 1 允许 red，阶段 2 结束 green，阶段 3-4 保持 green ✓
- Test First：阶段 2 先写测试（RED）再修复源码（GREEN）✓
- Checkpoint 作为每个 top-level task 的最后一个 sub-task ✓
- 手工测试 top-level task 的第一个 sub-task 为 "Increment alpha tag" ✓
- 所有 tasks 均为 mandatory，无 optional 标记 ✓
- 最后两个 top-level task：手工测试（Task 13）+ Code Review（Task 14）✓
- Notes section 提到执行时须遵循 spec-execution.md ✓

**Q6: 是否存在孤立代码或未集成的步骤？**

- Task 3.6 创建 `demo/` 目录和 `routes.yml`，需要在 `composer.json` 的 `autoload-dev` 中注册——已在 task 描述中明确 ✓
- Task 5.4 创建 `scripts/` 目录，Task 5.5 在 `vite.config.js` 中引用——有依赖关系，按序执行 ✓
- Task 11.2 创建 AST transform 脚本，Task 11.1 在迁移脚本中调用——有依赖关系，按序执行 ✓

**结论**: 所有 requirements、properties、决策均已覆盖，无遗漏，无孤立代码。


---

## Gatekeep Log

**校验时间**: 2025-07-14
**校验结果**: ⚠️ 已修正后通过

### 修正项

- [结构] 所有 13 个 Checkpoint sub-task 补充了"通过后 commit"动作——原文档 checkpoint 仅描述验证命令，缺少 spec-execution.md 要求的 commit 步骤
- [内容] Code Review task (14) 移除了展开的 review checklist 和 fix policy 描述（"按 checklist 逐文件检查代码风格……"、"发现问题直接修复"）——根据 gk-tasks 指引，review checklist 和 fix policy 由 code-reviewer agent 自身定义，不应在 task 描述中展开
- [内容] Notes section 补充了"Commit 随 checkpoint 一起执行"的明确说明——原文档 Notes 中缺少 commit 时机的显式声明

### 合规检查

- [x] 无 TBD / TODO / 待定 / 占位符
- [x] 无空 section 或不完整的列表
- [x] 内部引用一致（requirement 编号、design 中的模块名）
- [x] checkbox 语法正确（`- [ ]`）
- [x] 无 markdown 格式错误
- [x] `## Tasks` section 存在
- [x] Release spec 中手工测试类 top-level task 的第一个 sub-task 是 "Increment alpha tag"（Task 13.1）
- [x] 最后一个 top-level task 是 Code Review（Task 14）
- [x] 倒数第二个 top-level task 是手工测试（Task 13）
- [x] 自动化实现 task（1–12）排在手工测试和 Code Review 之前
- [x] 所有 task 使用 `- [ ]` checkbox 语法
- [x] top-level task 有序号（1–14），连续无跳号
- [x] sub-task 有层级序号，连续无跳号
- [x] requirements.md 中的每条 requirement（16/16）至少被一个 task 引用，无遗漏
- [x] 所有 AC 均有对应 task 覆盖（逐条验证通过）
- [x] 引用的 requirement 编号和 AC 编号在 requirements.md 中确实存在，无悬空引用
- [x] top-level task 按依赖关系排序，无循环依赖
- [x] Graphify 跨模块依赖查询已执行（TwigBridgeInfo、slimvue.js、SlimVueUpgradeCommand），task 排序与模块依赖一致
- [x] 每个 top-level task 的最后一个 sub-task 是 checkpoint
- [x] checkpoint 描述中包含具体验证命令和 commit 动作
- [x] 阶段 2（PHP/前端）遵循 test-first 编排（RED → GREEN）
- [x] 每个 sub-task 足够具体，可独立执行
- [x] 无过粗或过细的 task
- [x] 所有 task 均为 mandatory，无 optional 标记
- [x] 手工测试覆盖关键用户场景（PHP 集成、前端集成、迁移工具）
- [x] Code Review 是最后一个 top-level task，描述为委托给 code-reviewer sub-agent
- [x] Code Review task 不展开 review checklist 或 fix policy
- [x] `## Notes` section 存在
- [x] Notes 明确提到执行时须遵循 `spec-execution.md`
- [x] Notes 明确说明 commit 随 checkpoint 一起执行
- [x] Notes 包含当前 spec 特有的执行要点（PHP 命令约定、scripts/ 目录、迁移工具独立 task、AST 测试位置、TDD 节奏、PBT 标签格式）
- [x] Socratic Review 存在且覆盖度充分（Q1–Q6 覆盖 requirements、properties、design decisions、constraints、孤立代码）
- [x] Design CR Q1–Q4 所有决策均已在 tasks 编排中体现
- [x] Requirements CR Q1–Q4 所有决策均已在 tasks 编排中体现
- [x] design.md 中的所有模块、接口和实现项均有对应 task
- [x] 19 条 Correctness Properties 均有对应 PBT task
- [x] 验收闭环完整：checkpoint + 手工测试 + code review
- [x] 执行路径无歧义，task 排序和依赖关系清晰
