# SlimVue

PHP + Vue.js 前端脚手架库，为 Slim / oasis/http 等 PHP 框架提供 Vue.js 前端集成方案。通过 Twig 模板桥接 PHP 后端数据与 Vue.js 前端组件。

---

## 技术栈

### PHP 端（Composer 库）

| 项目 | 值 |
|------|-----|
| 包名 | `oasis/slimvue` |
| 命名空间 | `Oasis\SlimVue` |
| PHP 版本 | >= 8.5 |
| 依赖 | `oasis/http` ^3.1, `oasis/utils` ^3.0, `symfony/console` ^8.0, `symfony/filesystem` ^8.0 |
| 开发依赖 | `phpstan/phpstan` ^2.0, `phpunit/phpunit` ^13.0, `twig/twig` ^3.0, `giorgiosironi/eris` ~1.1 |
| 自动加载 | PSR-4: `Oasis\SlimVue\` → `src/` |

### 前端模板（slimvue-template）

| 项目 | 值 |
|------|-----|
| 框架 | Vue ^3.5 |
| 构建工具 | Vite ^7 |
| CSS 预处理 | Sass ^1.92 |
| 代码规范 | ESLint ^9（flat config）+ Prettier ^3.6 |
| 测试框架 | Vitest ^3 + @vue/test-utils ^2.4 |
| PBT | fast-check ^4 |
| Node.js | >= 24 |
| Node 入口 | `slimvue-template/src/entries/` 下的 `.js` 文件 |

---

## 版本号位置

| 位置 | 文件 |
|------|------|
| CLI 应用版本 | `bin/slimvue` — `new Application('slimvue', '4.0')` |
| Composer 包 | `composer.json` — `name` 字段（无 version 字段，由 VCS tag 决定） |
| 前端模板 | `slimvue-template/package.json` — `version` 字段（模板默认 `0.1.0`，初始化时重置） |

---

## 构建与运行命令

### PHP

本项目依赖 PHP 8.5+ 运行环境。所有 PHP 相关命令（composer、phpunit 等）直接使用 `php` 执行。

```bash
php $(which composer) install                          # 安装 PHP 依赖
php vendor/bin/phpunit                                 # 运行单元测试
php vendor/bin/phpunit --coverage-text                 # 运行测试并输出覆盖率
php vendor/bin/phpstan analyse                         # 静态分析（level 8）
```

### 前端（在 slimvue-template/ 或初始化后的项目目录下）

```bash
npm install               # 安装 Node 依赖（首次）
npm run dev               # Vite 开发服务器
npm run build             # 开发环境构建（含 Node.js 版本检查）
npm run release           # 生产环境构建（含 Node.js 版本检查）
npm run preview           # 预览构建产物
npm run lint              # ESLint 检查
npm run test              # Vitest 单次运行
npm run test:watch        # Vitest watch 模式
npm run test:coverage     # Vitest 覆盖率报告
```

### CLI

```bash
bin/slimvue initialize <project-name> [options]   # 初始化 slimvue 项目
bin/slimvue upgrade <project-dir>                 # 升级已有项目的模板文件
```

### 迁移工具

```bash
php bin/slimvue-migrate-check <project-dir>       # 检查项目迁移完成度
php bin/slimvue-migrate <project-dir>             # 自动执行可自动化的迁移步骤
```

---

## 运行入口

| 入口 | 文件 | 说明 |
|------|------|------|
| CLI | `bin/slimvue` | Symfony Console 应用，注册 `initialize` 和 `upgrade` 命令 |
| 开发服务器示例 | `index.php` | oasis/http（`MicroKernel`）应用，演示 Twig 渲染、Bridge 集成与错误处理 |
| 前端入口 | `slimvue-template/src/entries/*.js` | 每个 `.js` 文件对应一个页面入口 |
| 迁移验证 | `bin/slimvue-migrate-check` | 检查下游项目的 v3→v4 迁移完成度 |
| 迁移脚本 | `bin/slimvue-migrate` | 自动执行可自动化的 v3→v4 迁移步骤 |

---

## 敏感文件

| 文件 | 说明 |
|------|------|
| `slimvue-template/.env` | 构建环境变量（当前仅含 `BUILD_FILE_TYPE`） |
| `slimvue-template/.env.serve` | 开发服务器环境变量（含 mock bridge 数据） |
| `composer.lock` | 依赖锁定文件 |

---

## 目录结构概览

```
├── bin/                         # CLI 与迁移工具
│   ├── slimvue                  # CLI 入口
│   ├── slimvue-migrate          # 迁移脚本
│   ├── slimvue-migrate-check    # 迁移验证脚本
│   └── transforms/              # AST 转换脚本（vue2-to-vue3.js）
├── src/                         # PHP 源码
│   ├── SlimVueBridgeInterface.php   # Bridge 接口（typed params & returns）
│   ├── SlimVueInitializeCommand.php # initialize 命令
│   ├── SlimVueUpgradeCommand.php    # upgrade 命令（含字段缺失检测）
│   └── TwigBridgeInfo.php           # Bridge 实现（PHP→JS 数据传递）
├── demo/                        # 演示代码（autoload-dev）
│   ├── DemoController.php       # 路由控制器（多路由 + Bridge 数据类型演示）
│   └── DemoErrorHandler.php     # 框架级错误处理
├── slimvue-template/            # 前端项目模板
│   ├── scripts/                 # 构建辅助模块（ESM）
│   │   ├── entries.js           # 多页面入口扫描
│   │   ├── check-node.js       # Node.js 版本检查
│   │   └── tdk.js              # TDK 元数据注入 Vite 插件
│   ├── src/entries/             # 页面入口文件
│   ├── src/components/          # Vue 3 组件（script setup + Composition API）
│   ├── src/composables/         # 可复用逻辑（useClock 等）
│   ├── template/                # HTML/Twig 页面模板
│   ├── vite.config.js           # Vite 构建配置
│   ├── eslint.config.js         # ESLint flat config
│   └── slimvue.js               # 前端核心模块（bridge、mount、log）
├── docs/                        # 项目文档
├── issues/                      # Issue 管理
├── index.php                    # 开发服务器示例（oasis/http）
└── routes.yml                   # 路由配置
```
