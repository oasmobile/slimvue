# Migration Tools

v3 → v4 迁移工具的使用说明。SlimVue 提供两个 CLI 工具辅助迁移：`slimvue-migrate-check`（迁移检测）和 `slimvue-migrate`（自动迁移）。

---

## 前置条件

- 已安装 SlimVue v4（`composer require oasis/slimvue`）
- 已运行 `composer install`（需要 autoload）
- JS 文件的 AST 转换需要 Node.js 环境（不可用时自动回退到正则替换）

---

## slimvue-migrate-check

检测目标项目的 v4 迁移完成度，不修改任何文件。

### 用法

```bash
vendor/bin/slimvue-migrate-check <project-dir>
```

### 检查项

| 检查项 | 说明 | 通过条件 |
|--------|------|----------|
| PHP version constraint | `composer.json` 中的 PHP 版本约束 | `require.php` 满足 >= 8.5 |
| Deprecated dependencies | `composer.json` 和 `package.json` 中的过时依赖 | 不存在 `silex/silex`、`vue` (^2)、`@vue/cli-*`、`babel-*`、`jest`、`vue-template-compiler` 等 |
| Deprecated PHP API patterns | PHP 源码中的过时 API 调用 | 不存在 `Silex\Application`、`Silex\Provider`、Silex 路由注册等模式 |
| Deprecated JS/Vue API patterns | JS/Vue 源码中的过时 API 调用 | 不存在 `new Vue(`、`Vue.prototype`、`jest.fn()`、`jest.spyOn()`、`jest.mock()` 等模式 |
| Removed files | 应删除的过时文件/目录 | 不存在 `vue.config.js`、`babel.config.js`、`jest.config.js`、`build/` |

### 输出示例

```
✓ PHP version constraint: PHP constraint '>=8.5' satisfies >= 8.5
✗ Deprecated dependencies: Found deprecated dependencies: silex/silex — Replace with oasis/http
  → Remove or replace the listed dependencies
✓ Deprecated PHP API patterns: No deprecated patterns found
✗ Deprecated JS/Vue API patterns: Found deprecated patterns in 2 files
  → Update the listed API calls
✓ Removed files: No obsolete files found

Summary: 5 checks, 3 passed, 2 failed
```

### 退出码

- `0` — 所有检查通过
- `1` — 至少一个检查失败

---

## slimvue-migrate

对目标项目执行自动迁移，修改文件前会自动创建 `.bak` 备份。

### 用法

```bash
vendor/bin/slimvue-migrate <project-dir>
```

### 迁移步骤

脚本按顺序执行以下四个步骤：

**1. 更新 `composer.json`**

- 将 `require.php` 更新为 `>=8.5`
- 将 `silex/silex` 替换为 `oasis/http ^3.0`

**2. 删除过时文件**

移除以下文件和目录：

- `vue.config.js`
- `babel.config.js`
- `jest.config.js`
- `.eslintrc.js`
- `build/`（递归删除）

**3. 转换 JS/Vue 文件**

扫描项目中的 `.js`、`.vue`、`.ts`、`.jsx`、`.tsx` 文件，执行 Vue 2 → Vue 3 代码转换：

- `new Vue(` → `createApp(`
- `Vue.prototype` → `app.config.globalProperties`

转换策略：

- 优先使用 Node.js AST 转换（`bin/transforms/vue2-to-vue3.js`），仅修改代码段，保留注释和字符串
- Node.js 不可用或 AST 脚本缺失时，回退到正则替换（会输出 warning，建议手动复查）

**4. 更新 `package.json` scripts**

将 Vue CLI 命令替换为 Vite 命令：

| 原值 | 替换为 |
|------|--------|
| `vue-cli-service serve --mode serve` | `vite` |
| `vue-cli-service build --mode development` | `node scripts/check-node.js && vite build --mode development` |
| `vue-cli-service build --modern` | `node scripts/check-node.js && vite build` |
| `vue-cli-service lint` | `eslint .` |
| `jest --no-cache --coverage` | `vitest run --coverage` |
| `jest --no-cache` | `vitest run` |

同时标记过时的 NPM 依赖（如 `vue` ^2、`@vue/cli-*`、`babel-*`、`sass-loader` 等）为需要手动处理。

### 日志类型

| 图标 | 类型 | 含义 |
|------|------|------|
| `✓` | `change` | 成功修改 |
| `–` | `skip` | 跳过（文件不存在或无需修改） |
| `⚠` | `warning` | 警告（如 AST 转换不可用，回退到正则） |
| `✗` | `error` | 错误（备份失败、JSON 无效等） |
| `→` | `manual` | 需要手动处理 |

### 输出示例

```
✓ [change] Backup composer.json: Created backup: /path/to/composer.json.bak
✓ [change] Update PHP constraint: Changed require.php from '>=7.0' to '>=8.5'
✓ [change] Replace silex/silex: Removed silex/silex (^2.2) from require-dev, added oasis/http ^3.0
✓ [change] Remove obsolete file: Removed vue.config.js
✓ [change] Remove obsolete file: Removed babel.config.js
– [skip] Remove obsolete file: jest.config.js not found, skipping
✓ [change] Transform JS files: AST transform completed (2 files modified)
→ [manual] Deprecated NPM dependency: vue (^2.6) — Upgrade to vue ^3

Summary: 6 changes, 1 skipped, 0 warnings, 0 errors, 1 manual actions needed

Items requiring manual attention:
  → Deprecated NPM dependency: vue (^2.6) — Upgrade to vue ^3
```

### 退出码

- `0` — 迁移成功（可能仍有 warning 或 manual 项需要关注）
- `1` — 迁移失败（存在 error）

---

## 推荐工作流

1. **检测**：先运行 `slimvue-migrate-check` 了解当前状态
2. **自动迁移**：运行 `slimvue-migrate` 执行可自动化的步骤
3. **手动处理**：根据 `manual` 类型的输出完成剩余工作（依赖升级、组件重写等）
4. **再次检测**：运行 `slimvue-migrate-check` 确认所有检查通过
5. **验证**：运行构建和测试确保项目正常

完整的 breaking changes 和手动迁移步骤参见 `docs/manual/migration-v4.md`。
