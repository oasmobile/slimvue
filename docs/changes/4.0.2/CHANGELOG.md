# Changelog v4.0.2

本文件记录 v4.0.2 hotfix 的变更内容。

---

## 修复的 Issue

_无_

---

## 依赖变更

- `oasis/http` 从 `require-dev` 移至 `require`，版本升级至 `^3.1`
- `oasis/utils` 保持 `^3.0`（上游无 3.1）
- 新增 `phpstan/phpstan` `^2.0`（require-dev）

---

## 代码质量

- 新增 PHPStan level 8 静态分析配置（`phpstan.neon`），修复全部 19 处类型错误
- ESLint 加入 `@eslint/js` recommended 及严格规则（eqeqeq, no-var, prefer-const, no-shadow 等）
- 修复前端代码中 9 处未使用变量/导入

---

## 文档变更

- 修正 `MigrationScript`：将 `oasis/http` 正确加入 `require`（而非 `require-dev`）
- 修正 migration guide 中 `oasis/http` 的类型和版本号
- 同步 `docs/state/architecture.md` 反映 PHPStan 和 oasis/http 变更

---

## 测试覆盖

- PHPUnit：169 tests, 1955 assertions — 全部通过
- PHPStan level 8：0 errors
- ESLint：0 errors
- Vitest：118 tests — 全部通过
