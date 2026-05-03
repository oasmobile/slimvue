# Data Model

数据模型与接口定义（SSOT）。

---

## PHP 接口

### `SlimVueBridgeInterface`

```php
namespace Oasis\SlimVue;

interface SlimVueBridgeInterface
{
    public function getExecTwig($pageTwig);  // 将页面 twig 路径转换为控制器 twig 路径
    public function add($key, $value);       // 添加键值对到 bridge 数据
    public function render();                // 将数据序列化为 JSON 字符串
}
```

### `TwigBridgeInfo`（实现类）

- 构造函数接受初始数据数组 `$data`
- `add()` 支持嵌套数组和 `JsonSerializable` 对象，递归转换为普通值
- `getExecTwig()` 将 `slimvue/pages/` 前缀替换为 `slimvue/controllers/`
- `render()` 返回 JSON 字符串，编码失败时抛出 `InvalidArgumentException`

---

## 前端核心模块（slimvue.js）

### 导出对象属性

| 属性/方法 | 类型 | 说明 |
|-----------|------|------|
| `bridge` | getter | 读取 `window.bridge`；未定义时 fallback 到 `VUE_APP_BRIDGE` 环境变量 |
| `isDebug` | getter | `process.env.NODE_ENV !== "production"` |
| `logLevel` | getter/setter | 日志级别，默认 debug 模式为 0，非 debug 为 20 |
| `mount(vueComponent)` | function | 创建 Vue 实例并挂载到 `#app`，返回 Vue 实例 |
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
| `ASSETS_DIR` | 静态资源子目录 | — |
| `EXCLUED_ENTRIES` | 排除的入口（逗号分隔） | — |
| `VUE_APP_BRIDGE` | 开发模式下的 mock bridge JSON | — |

### 页面入口生成规则

- 扫描 `src/entries/` 下所有 `.js` 文件
- 入口 key = 相对路径去掉 `.js` 后缀，`/` 替换为 `-`
- 输出文件名 = `pages/` + 相对路径，后缀替换为 `BUILD_FILE_TYPE`
- 模板文件 = `template/index.${BUILD_FILE_TYPE}`
