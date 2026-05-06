# Spec Goal: Frontend Toolchain Upgrade (v4.1)

## 来源

- 分支: `release/4.1`
- 需求文档: `docs/proposals/PRP-002-frontend-toolchain-upgrade.md`

## 背景摘要

PRP-001（v4.0）完成后，前端工具链已处于较新状态。但自那次升级以来，三个核心工具发布了新的大版本：Vite 8（Rolldown 统一打包器）、Vitest 4、ESLint 10。其余依赖也有小版本更新可跟进。

本次 release 4.1 是一次前端工具链大版本升级，不涉及 PHP 端变更，不引入新功能特性。

## 目标

- 将 Vite 升级到 ^8（Rolldown 统一打包器）
- 将 Vitest 升级到 ^4（含 @vitest/coverage-v8）
- 将 ESLint 升级到 ^10
- 将所有其余 npm 依赖升级到当前大版本线内的最新小版本
- 确保现有测试全部通过（green）
- 确保 `npm run build` / `npm run release` 产物正常
- 确保 `npm run lint` 无新增错误
- 更新 `docs/state/` 中涉及版本的描述（如有）

## 不做的事情（Non-Goals）

- 不升级 Node.js 最低版本要求（保持 `>=24`）
- 不升级 Vue 大版本（仍为 Vue 3.5）
- 不引入新功能特性
- 不变更项目架构或目录结构
- 不做 PHP 端任何变更

## Clarification 记录

### Q1: 升级执行顺序

- 选项:
  A) 一次性全部升级，统一验证
  B) 分步升级：先 Vite 8 → 再 Vitest 4 → 再 ESLint 10 → 最后小版本，每步验证
  C) 分两批：构建链（Vite + Vitest）一批，Lint 链（ESLint）一批
  D) 补充说明
- 回答: C — 构建链（Vite 8 + Vitest 4）一批，Lint 链（ESLint 10）一批，分批验证

### Q2: 配置适配深度

- 选项:
  A) 最小适配——仅修复 breaking changes 导致的报错，不主动优化配置
  B) 适度优化——修复 breaking + 采用新推荐写法（如有）
  C) 补充说明
- 回答: B — 修复 breaking changes 的同时采用新推荐写法

### Q3: lock 文件处理

- 选项:
  A) 删除后重新 `npm install` 生成全新 lock
  B) 通过 `npm install` 增量更新 lock
  C) 补充说明
- 回答: A — 删除 lock 后重新生成

### Q4: state 文档更新范围

- 选项:
  A) 仅更新明确列出版本号的地方
  B) 同时更新相关工具描述（如提及 esbuild/Rollup 的地方改为 Rolldown）
  C) release finish 阶段再统一更新
  D) 补充说明
- 回答: B — 同时更新版本号和相关工具描述

## 约束与决策

- **不走 feature 分支**: PRP-002 直接在 release/4.1 上开发
- **执行顺序**: 分两批——构建链（Vite 8 + Vitest 4 + @vitest/coverage-v8）先行，Lint 链（ESLint 10）跟进，最后处理其余小版本
- **配置适配**: 修复 breaking + 采用新推荐写法
- **lock 文件**: 删除后重新生成
- **state 更新**: 版本号 + 相关工具描述同步更新
- **验证标准**: `npm run test` green、`npm run build` / `npm run release` 成功、`npm run lint` 无新增错误
