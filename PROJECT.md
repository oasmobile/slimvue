# SlimVue

PHP + Vue.js 前端脚手架库，为 Slim/Silex 等 PHP 框架提供 Vue.js 前端集成方案。通过 Twig 模板桥接 PHP 后端数据与 Vue.js 前端组件。

---

## 技术栈

### PHP 端（Composer 库）

| 项目 | 值 |
|------|-----|
| 包名 | `oasis/slimvue` |
| 命名空间 | `Oasis\SlimVue` |
| PHP 版本 | >= 7.0 |
| 依赖 | `oasis/utils` ^1.7, `symfony/console` ^4.0, `symfony/filesystem` ^4.0, `oasis/flysystem-wrappers` ^1.3 |
| 开发依赖 | `silex/silex` ^2.2, `twig/twig` ^1.0 |
| 自动加载 | PSR-4: `Oasis\SlimVue\` → `src/` |

### 前端模板（slimvue-template）

| 项目 | 值 |
|------|-----|
| 框架 | Vue 2.6 |
| 构建工具 | Vue CLI 4 (webpack) |
| CSS 预处理 | Sass |
| 代码规范 | ESLint + Prettier |
| Node 入口 | `slimvue-template/src/entries/` 下的 `.js` 文件 |

---

## 版本号位置

| 位置 | 文件 |
|------|------|
| CLI 应用版本 | `bin/slimvue` — `new Application('slimvue', "1.4")` |
| Composer 包 | `composer.json` — `name` 字段（无 version 字段，由 VCS tag 决定） |
| 前端模板 | `slimvue-template/package.json` — `version` 字段（模板默认 `0.1.0`，初始化时重置） |

---

## 构建与运行命令

### PHP

本项目依赖 PHP 7.4 运行环境，本机通过 `php74` alias 调用。所有 PHP 相关命令（composer、phpunit 等）均需通过该 alias 执行。

```bash
php74 $(which composer) install                          # 安装 PHP 依赖
php74 vendor/bin/phpunit                                 # 运行单元测试
```

### 前端（在 slimvue-template/ 或初始化后的项目目录下）

```bash
npm install               # 安装 Node 依赖（首次）
npm run serve             # 开发服务器（webpack dev server，端口 8090）
npm run build             # 开发环境构建
npm run watch             # 开发环境构建 + 文件监听
npm run release           # 生产环境构建（--modern）
npm run lint              # ESLint 检查
```

### CLI

```bash
bin/slimvue initialize <project-name> [options]   # 初始化 slimvue 项目
bin/slimvue upgrade <project-dir>                 # 升级已有项目的模板文件
```

---

## 运行入口

| 入口 | 文件 | 说明 |
|------|------|------|
| CLI | `bin/slimvue` | Symfony Console 应用，注册 `initialize` 和 `upgrade` 命令 |
| 开发服务器示例 | `index.php` | Silex 应用，演示 Twig 渲染与 bridge 集成 |
| 前端入口 | `slimvue-template/src/entries/*.js` | 每个 `.js` 文件对应一个页面入口 |

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
├── bin/slimvue                  # CLI 入口
├── src/                         # PHP 源码
│   ├── SlimVueBridgeInterface.php   # Bridge 接口
│   ├── SlimVueInitializeCommand.php # initialize 命令
│   ├── SlimVueUpgradeCommand.php    # upgrade 命令
│   └── TwigBridgeInfo.php           # Bridge 实现（PHP→JS 数据传递）
├── slimvue-template/            # 前端项目模板
│   ├── build/                   # Webpack 构建配置
│   ├── src/entries/             # 页面入口文件
│   ├── src/components/          # Vue 组件
│   ├── template/                # HTML/Twig 页面模板
│   └── slimvue.js               # 前端核心模块（bridge、mount、log）
├── docs/                        # 项目文档
├── issues/                      # Issue 管理
└── index.php                    # 开发服务器示例
```
