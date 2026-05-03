# Spec Goal: Full Stack Upgrade (v4.0)

## 来源

- 分支: `release/4.0`
- 需求文档: `docs/proposals/PRP-001-full-stack-upgrade.md`

## 背景摘要

SlimVue 是一个 PHP Composer 库，为 Slim/Silex 等 PHP 框架提供 Vue.js 前端集成方案。核心能力包括：前端项目模板（多页面入口脚手架）、CLI 工具（initialize / upgrade）、PHP-JS Bridge（通过 Twig 将后端数据注入前端）。

当前技术栈严重滞后——PHP >= 7.0、Vue 2（已 EOL）、Vue CLI 4（已停止维护）、Silex（已废弃）、Jest、ESLint 6、Prettier 1 等。多个核心依赖已进入 EOL 或停止维护，存在安全风险和生态脱节。

本次 release 4.0 是一次全栈 breaking 升级，覆盖 PHP 端和前端体系的所有依赖、语法、测试框架，并产出面向下游用户的完整迁移工具链。

## 目标

- 将 PHP 最低版本要求升级到 8.5，全面采用 PHP 8.5 语法特性
- 将 PHPUnit 升级到 ^13.0，适配新 API
- 将所有 Composer 依赖升级到最新大版本
- 将 Silex 替换为 `oasis/http`，`index.php` 示例增强为更完整的参考（多页面入口演示、bridge 数据类型演示等）
- 将 Vue 2 迁移到 Vue 3，重新设计模板组件（丰富的示例，展示 `<script setup>`、Composition API、composables、`defineProps`/`defineEmits`、响应式 API 等）
- 将构建工具从 Vue CLI (webpack) 迁移到 Vite
- 将前端测试从 Jest 迁移到 Vitest
- 将所有 npm 依赖升级到最新大版本，Node.js 最低要求 >= 24
- Node.js 版本约束通过 `package.json` engines 字段 + 构建脚本版本检查双重保障
- 引入 PBT：PHP 端使用 eris 1.1，JS 端使用 fast-check
- 测试覆盖率（UT + PBT + Integration）> 90%
- 产出完整的迁移文档、迁移验证脚本、迁移脚本（均为正式交付物）
- 将 `slimvue-template/coverage/` 从版本控制中移除，加入 `.gitignore`

## 不做的事情（Non-Goals）

- 不改变 SlimVue 的功能定位和核心架构（Bridge 机制、多页面入口、CLI 工具）
- 不改变目录结构的顶层组织方式
- 不考虑旧版本兼容——这是一次 breaking 升级
- 不引入新的功能特性（本次纯升级，`index.php` 示例增强和模板组件重新设计属于升级范畴内的改进）

## Clarification 记录

### Q1: Release 4.0 的 scope 分阶段策略

- 选项:
  A) 一次性完成 PRP-001 全部 scope（PHP 4 阶段 + 前端 4 阶段 + 迁移产出物）
  B) 只做 PHP 端
  C) 只做阶段 1-2
  D) 补充说明
- 回答: A — 全部在 release/4.0 中交付

### Q2: 迁移产出物的定位

- 选项:
  A) 作为正式交付物，与代码升级一起完成
  B) 文档必须有，脚本 best-effort
  C) 全部推迟
  D) 补充说明
- 回答: A — 迁移文档、验证脚本、迁移脚本全部作为正式交付物

### Q3: PHP 端 PBT 库选型

- 选项:
  A) eris
  B) phpunit-property-based-testing
  C) 手写 PBT 风格测试
  D) design 阶段再调研
  E) 补充说明
- 回答: A — 使用 eris 1.1

### Q4: `index.php` 中 Silex → `oasis/http` 的替换深度

- 选项:
  A) 功能对等替换
  B) 借机简化
  C) 借机增强
  D) 补充说明
- 回答: C — 替换框架的同时补充更多示例场景，使 `index.php` 成为更完整的参考

### Q5: 前端组件迁移到 Vue 3 时的处理策略

- 选项:
  A) 全部保留逐个迁移
  B) 精简后迁移
  C) 重新设计模板组件
  D) 补充说明
- 回答: C — 重新设计，且要丰富一些

### Q6: Node.js 版本约束

- 最低版本: PRP-001 原定 >= 20，但 Node 20 已于 2026-04 EOL，用户选择提升到 >= 24（最新 LTS）
- 执行方式选项:
  A) 仅 engines 字段
  B) engines + 构建脚本版本检查
  C) 仅文档说明
  D) 补充说明
- 回答: B — engines 字段 + 构建脚本版本检查双重保障

### Q7: 测试覆盖率统计文件处理

- 选项:
  A) 从版本控制移除，加入 .gitignore
  B) 保留现状
  C) 补充说明
- 回答: A — 覆盖率报告是 CI 产物，不应 track

## 约束与决策

- **执行顺序**: PHP 端先行，前端跟进；每端按"升级依赖 → 升级测试 → 升级语法 → PBT + 覆盖率"4 阶段推进
- **TDD 节奏**: 阶段 1 允许 red，阶段 2 结束必须 green，阶段 3-4 保持 green
- **PBT 选型**: PHP 端 eris 1.1，JS 端 fast-check
- **Node.js**: >= 24，engines + 构建脚本双重检查
- **Silex 替代**: `oasis/http`，示例增强
- **Vue 3 组件**: 重新设计，丰富展示 Vue 3 特性
- **构建工具**: Vite 替代 Vue CLI
- **测试框架**: Vitest 替代 Jest
- **覆盖率产物**: 从 git 移除，加入 .gitignore
- **迁移产出物**: 文档 + 验证脚本 + 迁移脚本，全部为正式交付物
- **不走 feature 分支**: PRP-001 直接在 release/4.0 上开发
