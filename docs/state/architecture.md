# Architecture

系统架构与工程约束（SSOT）。

---

## 系统定位

SlimVue 是一个 PHP Composer 库，提供：

1. **前端项目模板**：基于 Vue 2 + Vue CLI 4 的多页面应用脚手架
2. **CLI 工具**：初始化和升级前端项目
3. **PHP-JS Bridge**：通过 Twig 模板将 PHP 后端数据注入前端

---

## 分层结构

```
┌─────────────────────────────────────────────┐
│  PHP 应用（Slim / Silex / 其他框架）          │
│  ├── Twig 渲染层                             │
│  │   └── TwigBridgeInfo → window.bridge      │
│  └── 路由 & 控制器                            │
├─────────────────────────────────────────────┤
│  SlimVue CLI（Symfony Console）               │
│  ├── initialize：从模板创建前端项目            │
│  └── upgrade：用最新模板更新已有项目           │
├─────────────────────────────────────────────┤
│  前端（Vue 2 多页面应用）                      │
│  ├── slimvue.js 核心模块                      │
│  │   ├── bridge 数据读取                      │
│  │   ├── mount() 挂载 Vue 组件                │
│  │   └── 日志系统                             │
│  ├── entries/ 页面入口                        │
│  └── components/ Vue 组件                     │
└─────────────────────────────────────────────┘
```

---

## 技术选型

| 层 | 技术 | 版本约束 |
|----|------|----------|
| PHP 运行时 | PHP | >= 7.0 |
| PHP CLI 框架 | Symfony Console | ^4.0 |
| 文件系统操作 | Symfony Filesystem + oasis/flysystem-wrappers | ^4.0 / ^1.3 |
| 模板引擎 | Twig | ^1.0（dev） |
| 前端框架 | Vue.js | ^2.6 |
| 构建工具 | Vue CLI Service (webpack) | ^4.3 |
| CSS 预处理 | Sass | ^1.26 |
| 代码规范 | ESLint + Prettier | — |

---

## Bridge 机制

PHP 后端通过 `TwigBridgeInfo` 将数据序列化为 JSON，注入 Twig 模板中的 `<script>` 标签：

```
PHP (TwigBridgeInfo::render()) → Twig (window.bridge = {{ bridge.render()|raw }}) → JS (slimvue.bridge)
```

- `TwigBridgeInfo` 实现 `SlimVueBridgeInterface`
- 支持嵌套数组和 `JsonSerializable` 对象
- 前端通过 `slimvue.bridge` 访问，开发模式下 fallback 到 `VUE_APP_BRIDGE` 环境变量

---

## 多页面入口机制

- 入口文件位于 `src/entries/` 目录
- 构建系统自动扫描该目录下所有 `.js` 文件（含子目录），每个文件生成一个独立页面
- 页面输出到 `dist/pages/`，文件类型由 `BUILD_FILE_TYPE` 环境变量决定（`twig` 或 `html`）
- 可通过 `EXCLUED_ENTRIES` 环境变量排除特定入口

---

## CLI 命令

### `initialize`

从 `slimvue-template/` 复制模板到目标目录，并执行：

- 重写 `package.json` 的 `name` 和 `version`
- 创建 Twig 模板目录的符号链接
- 创建静态资源目录（fonts/js/img/css/static）的符号链接
- 生成 `slimvue.services.yml` 服务配置文件

参数与选项：

| 参数/选项 | 说明 | 默认值 |
|-----------|------|--------|
| `project-name` | 项目名称（小写字母、数字、连字符） | — |
| `--scope` | npm scope | 无 |
| `--directory, -d` | 安装目录 | `./slimvue-<project-name>` |
| `--twig, -t` | Twig 模板基础目录 | `./templates` |
| `--service-dir` | 服务配置文件目录 | `./config` |
| `--web-dir, -w` | Web 资源目录 | `./web` |

### `upgrade`

用最新模板覆盖目标目录，保留原有的 `name`、`version`、`dependencies`、`devDependencies`（合并策略：新模板的依赖覆盖旧的同名依赖）。
