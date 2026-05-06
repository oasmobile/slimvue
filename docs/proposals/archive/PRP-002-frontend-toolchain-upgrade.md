# PRP-002 Frontend Toolchain Upgrade

前端构建与开发工具链大版本升级 proposal，将 Vite、Vitest、ESLint 升级到最新大版本，同步更新其余小版本依赖。

---

## Status

`released`

---

## Background

PRP-001 完成后，前端工具链已处于较新状态。但自那次升级以来，三个核心工具发布了新的大版本：

| 工具 | 当前版本 | 最新 stable |
|------|----------|-------------|
| Vite | ^7 | 8.x |
| Vitest | ^3 | 4.x |
| ESLint | ^9 | 10.x |

其余依赖也有小版本更新可跟进：

| 工具 | 当前版本 | 最新 stable | 备注 |
|------|----------|-------------|------|
| @vitejs/plugin-vue | ^6 | 6.0.6（无新大版本） | 6.0.3 起已支持 Vite 8 |
| @vue/test-utils | ^2.4 | 2.x（最新小版本） | |
| Prettier | ^3.6 | 3.8.x | |
| sass | ^1.92 | 1.92.x | 无新大版本 |
| autoprefixer | ^10.5.0 | 10.x | 无新大版本 |
| eslint-config-prettier | ^10 | 最新小版本 | |
| eslint-plugin-vue | ^10 | 最新小版本 | |
| jsdom | ^29.1.1 | 最新小版本 | |
| fast-check | ^4 | 最新小版本 | |
| @vitest/coverage-v8 | ^3.2.4 | 需跟随 Vitest 4 | |

---

## Problem

1. **Vite 8 是架构级变更**：用 Rolldown（Rust）统一替代了 esbuild + Rollup 双引擎，构建速度提升 10–30×，消除 dev/prod 行为不一致
2. **Vitest 4 需要适配 Vite 8**：Vitest 4.1 起正式支持 Vite 8；继续使用 Vitest 3 + Vite 8 不受支持
3. **ESLint 10 是生态趋势**：eslint-plugin-vue 等插件后续版本可能仅支持 ESLint 10+，提前跟进避免被动升级
4. 小版本依赖滞后会累积技术债，定期跟进成本更低

---

## Goals

- 将 Vite 升级到 ^8（Rolldown 统一打包器）
- 将 Vitest 升级到 ^4（含 @vitest/coverage-v8）
- 将 ESLint 升级到 ^10
- 将所有其余 npm 依赖升级到当前大版本线内的最新小版本
- 确保现有测试全部通过（green）
- 确保 `npm run build` / `npm run release` 产物正常
- 确保 `npm run lint` 无新增错误
- 更新 `docs/state/` 中涉及版本的描述（如有）

---

## Non-Goals

- 不升级 Node.js 最低版本要求（保持 `>=24`）
- 不升级 Vue 大版本（仍为 Vue 3.5）
- 不引入新功能特性
- 不变更项目架构或目录结构
- 不做 PHP 端任何变更

---

## Scope

### 依赖变更

| 包 | 变更 |
|----|------|
| `vite` | ^7 → ^8 |
| `vitest` | ^3 → ^4 |
| `@vitest/coverage-v8` | ^3.2.4 → ^4 |
| `eslint` | ^9 → ^10 |
| `@vitejs/plugin-vue` | ^6（无新大版本，更新到最新 6.x 小版本） |
| `eslint-config-prettier` | ^10 → 最新 |
| `eslint-plugin-vue` | ^10 → 最新 |
| `@vue/test-utils` | ^2.4 → 最新 2.x |
| `prettier` | ^3.6 → ^3.8 |
| `jsdom` | ^29.1.1 → 最新 |
| `fast-check` | ^4 → 最新 4.x |
| `sass` | ^1.92（无新大版本，更新到最新小版本） |
| `autoprefixer` | ^10.5.0（无新大版本，更新到最新小版本） |

### 配置适配

| 文件 | 可能的变更 |
|------|-----------|
| `vite.config.js` | 适配 Vite 8 / Rolldown 配置变更（如有） |
| `eslint.config.js` | 适配 ESLint 10 API 变更（多配置文件支持、规则语言声明等） |
| `vitest` 相关配置 | 适配 Vitest 4 API 变更（Browser Mode provider 包拆分等，如涉及） |

### 验证

| 验证项 | 命令 |
|--------|------|
| 测试通过 | `npm run test` |
| 构建成功 | `npm run build` && `npm run release` |
| Lint 通过 | `npm run lint` |

---

## Risks

- Vite 8 的 Rolldown 引擎对部分 Rollup 插件可能存在兼容性边缘情况（本项目插件较少，风险低）
- ESLint 10 的 breaking changes 可能影响自定义规则配置
- Vitest 4 的 Browser Mode API 变更——本项目未使用 Browser Mode，影响极小
- `@vitest/coverage-v8` 大版本跟随 Vitest，需同步升级

---

## References

- `slimvue-template/package.json`
- `slimvue-template/vite.config.js`
- `slimvue-template/eslint.config.js`
- [Vite 8 公告](https://main.vitejs.dev/blog/announcing-vite8)
- [Vitest 4 公告](https://cn.vitest.dev/blog/vitest-4)
- [ESLint 10 发布](https://eslint.org/blog/2026/02/eslint-v10.0.0-released/)
