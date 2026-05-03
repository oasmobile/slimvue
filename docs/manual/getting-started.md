# Getting Started

SlimVue 使用指南。

---

## 安装

SlimVue 作为 Composer 依赖安装到 PHP 项目中：

```bash
composer require oasis/slimvue
```

---

## 初始化前端项目

```bash
vendor/bin/slimvue initialize <project-name> [options]
```

示例：

```bash
vendor/bin/slimvue initialize myapp --scope myorg --twig ./templates --web-dir ./web
```

该命令会：

1. 将前端模板复制到 `./slimvue-myapp/`（或 `--directory` 指定的目录）
2. 设置 `package.json` 的 `name` 为 `@myorg/myapp`，`version` 为 `0.1.0`
3. 创建 Twig 模板目录符号链接（`./templates/slimvue` → `dist/pages`）
4. 创建静态资源符号链接（`./web/js`、`./web/css` 等 → `dist/` 对应子目录）
5. 生成 `./config/slimvue.services.yml` 服务配置文件

### 常用选项

| 选项 | 说明 | 默认值 |
|------|------|--------|
| `--scope` | npm scope（如 `myorg`） | 无 |
| `-d, --directory` | 前端项目安装目录 | `./slimvue-<name>` |
| `-t, --twig` | Twig 模板基础目录 | `./templates` |
| `--service-dir` | 服务配置文件目录 | `./config` |
| `-w, --web-dir` | Web 资源目录 | `./web` |

---

## 前端开发

进入初始化后的前端项目目录：

```bash
npm install           # 首次安装依赖
npm run dev           # 启动 Vite 开发服务器
npm run build         # 开发环境构建
npm run release       # 生产环境构建
npm run preview       # 预览构建产物
npm run test          # 运行测试
npm run test:watch    # 测试 watch 模式
npm run lint          # ESLint 检查
```

---

## 添加新页面

1. 在 `src/entries/` 下创建新的 `.js` 入口文件（支持子目录）
2. 在 `src/components/` 下创建对应的 Vue 组件
3. 入口文件中挂载组件：

```javascript
import slimvue from "slimvue";
import MyPage from "@/components/MyPage.vue";
slimvue.mount(MyPage);
```

构建系统会自动识别新入口并生成对应页面。

---

## PHP 端集成

### 注入 Bridge 数据

在 PHP 控制器中创建 `TwigBridgeInfo` 并传递给 Twig 模板：

```php
use Oasis\SlimVue\TwigBridgeInfo;

$bridge = new TwigBridgeInfo(['user' => $currentUser]);
$bridge->add('config', $appConfig);

return $twig->render('slimvue/pages/index.twig', [
    'title'  => '页面标题',
    'bridge' => $bridge,
]);
```

### 服务配置（推荐）

导入生成的服务配置文件，将 bridge 注册为全局 Twig 变量：

```yaml
# services.yml
imports:
    - { resource: "slimvue.services.yml" }

app:
    http:
        twig:
            globals:
                bridge: "@slimvue.bridge"
```

---

## 前端读取 Bridge 数据

```javascript
import slimvue from "slimvue";

const userData = slimvue.bridge.user;
const config = slimvue.bridge.config;
```

开发模式下（`npm run dev`），bridge 数据从 `.env.serve` 的 `VITE_BRIDGE` 环境变量读取。

---

## 升级模板

当 SlimVue 库更新后，可升级已有项目的构建配置：

```bash
vendor/bin/slimvue upgrade <project-dir>
```

升级会用最新模板覆盖构建文件，同时保留项目的 `name`、`version` 和自定义依赖。升级后建议检查 git diff 确认变更。

注意：目标项目的 `package.json` 必须包含 `name`、`version`、`dependencies`、`devDependencies` 四个字段，否则命令将中止并报错。
