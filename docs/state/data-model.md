# Data Model

数据模型与接口定义（SSOT）。

---

## PHP 接口

### `SlimVueBridgeInterface`

```php
namespace Oasis\SlimVue;

interface SlimVueBridgeInterface
{
    public function getExecTwig(string $pageTwig): string;
    public function add(string $key, mixed $value): void;
    public function render(): string;
}
```

### `TwigBridgeInfo`（实现类）

- 使用 constructor promotion（`private array $data`）
- `add()` 支持嵌套数组和 `JsonSerializable` 对象，递归转换为普通值
- `getExecTwig()` 将 `slimvue/pages/` 前缀替换为 `slimvue/controllers/`
- `render()` 返回 JSON 字符串，编码失败时抛出 `InvalidArgumentException`

---

## 前端核心模块（slimvue.js）

### 导出对象属性

| 属性/方法 | 类型 | 说明 |
|-----------|------|------|
| `bridge` | getter | 读取 `window.bridge`；未定义时 fallback 到 `VITE_BRIDGE` 环境变量 |
| `isDebug` | getter | `import.meta.env.MODE !== "production"` |
| `logLevel` | getter/setter | 日志级别，默认 debug 模式为 0，非 debug 为 20 |
| `mount(vueComponent)` | function | 调用 `createApp(vueComponent)` 创建 Vue 3 应用实例，注入全局属性（`$toCssUrl`、`$toCssBackgroundImage`），挂载到 `#app`，赋值给 `window.slimvue`，返回 App 实例 |
| `log/debug/info/warn/error` | function | 按级别输出日志 |

### 日志级别常量

| 常量 | 值 |
|------|-----|
| `DEBUG_LOG_LEVEL` | 0 |
| `DEFAULT_LOG_LEVEL` | 10 |
| `INFO_LOG_LEVEL` | 20 |
| `WARNING_LOG_LEVEL` | 30 |
| `ERROR_LOG_LEVEL` | 40 |

---

## 构建配置

### 环境变量

| 变量 | 说明 | 默认值 |
|------|------|--------|
| `BUILD_FILE_TYPE` | 输出文件类型（`twig` / `html`） | `twig` |
| `PUBLIC_PATH` | 公共路径前缀 | `/` |
| `OUTPUT_DIR` | 输出目录 | `dist` |
| `EXCLUED_ENTRIES` | 排除的入口（逗号分隔） | — |
| `VITE_BRIDGE` | 开发模式下的 mock bridge JSON | — |

### Node.js 版本检查

`scripts/check-node.js` 提供版本检查功能，同时支持独立脚本执行和模块导入：

- 独立执行：`node scripts/check-node.js`（顶层代码立即检查 >= 24）
- 模块导入：`checkNodeVersion(min)` 函数供 `vite.config.js` 调用
- `package.json` 的 `build` 和 `release` scripts 中通过独立执行方式调用

### 页面入口生成规则

- `scripts/entries.js` 扫描 `src/entries/` 下所有 `.js` 文件
- 入口 key = 相对路径去掉 `.js` 后缀，`/` 替换为 `-`
- 输出文件名 = `pages/` + 相对路径，后缀替换为 `BUILD_FILE_TYPE`
- 模板文件 = `template/index.${BUILD_FILE_TYPE}`

### TDK 元数据注入

`scripts/tdk.js` 提供 Vite 插件，通过 `transformIndexHtml` 钩子为每个页面注入 title、description、keywords 元数据。
