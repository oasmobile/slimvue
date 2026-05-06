# Migration Guide: v3 → v4

从 SlimVue v3 升级到 v4 的完整迁移指南。v4 是一次全栈 breaking 升级，涵盖 PHP 端、前端、CLI 工具三大领域。

---

## 推荐迁移顺序

建议按以下顺序分步迁移，每步完成后可独立验证：

1. **PHP 端**：升级 PHP 版本、更新 Composer 依赖、适配 API 变更 → 验证：`php $(which composer) install` 成功
2. **前端基础设施**：删除旧文件、创建 `vite.config.js` 和 `eslint.config.js`、更新 `package.json`（依赖 + scripts + type + engines） → 验证：`npm install` 成功
3. **前端代码**：迁移 `slimvue.js`、环境变量、Vue 组件、测试 → 验证：`npm run build` + `npm run test` 通过
4. **最终验证**：对照末尾 Summary Checklist 逐项确认

> 也可以使用迁移工具自动完成部分步骤，参见末尾 [Migration Tools](#migration-tools) 章节。

---

## PHP Breaking Changes

### PHP 版本要求

| 项目 | v3 | v4 |
|------|-----|-----|
| PHP 最低版本 | >= 7.0 | >= 8.5 |

**迁移步骤：**

1. 确认运行环境已安装 PHP 8.5+
2. 更新 `composer.json` 中 `require.php` 为 `>=8.5`
3. 运行 `php $(which composer) install` 验证

### Composer 依赖替换

| 依赖 | v3 | v4 | 类型 |
|------|-----|-----|------|
| `symfony/console` | ^4.0 | ^8.0 | require |
| `symfony/filesystem` | ^4.0 | ^8.0 | require |
| `oasis/utils` | ^1.7 | ^3.0 | require |
| `oasis/flysystem-wrappers` | ^1.3 | 已移除 | require |
| `silex/silex` | ^2.2 | 已移除 | require-dev |
| `oasis/http` | — | ^3.1（新增） | require |
| `twig/twig` | ^1.0 | ^3.0 | require-dev |
| `phpunit/phpunit` | ^9.0 | ^13.0 | require-dev |
| `giorgiosironi/eris` | — | ~1.1（新增） | require-dev |

**迁移步骤：**

1. 更新 `composer.json` 中的依赖版本约束
2. 将 `silex/silex` 替换为 `oasis/http`
3. 移除 `oasis/flysystem-wrappers`（如项目直接依赖）
4. 运行 `php $(which composer) install` 解决冲突

### Symfony Console ^8 API 变更

- `Command::execute()` 返回类型必须为 `int`（`Command::SUCCESS` / `Command::FAILURE`）
- 部分 deprecated 方法已移除，需使用新 API

**迁移步骤：**

1. 检查所有继承 `Command` 的类，确保 `execute()` 方法声明返回类型 `int`
2. 将 `return 0` 替换为 `return Command::SUCCESS`，`return 1` 替换为 `return Command::FAILURE`
3. 查阅 [Symfony Console 升级文档](https://symfony.com/doc/current/console.html) 处理其他 deprecated API

### Twig ^3 API 变更

- `Twig_Environment` → `Twig\Environment`
- `Twig_Loader_Filesystem` → `Twig\Loader\FilesystemLoader`
- 其他 `Twig_*` 前缀类均已移至 `Twig\` 命名空间

**迁移步骤：**

1. 全局搜索 `Twig_` 前缀，替换为 `Twig\` 命名空间下的对应类
2. 更新 `use` 语句

### `SlimVueBridgeInterface` 类型声明变更

v3（无类型声明）：

```php
public function getExecTwig($pageTwig);
public function add($key, $value);
public function render();
```

v4（完整类型声明）：

```php
public function getExecTwig(string $pageTwig): string;
public function add(string $key, mixed $value): void;
public function render(): string;
```

**迁移步骤：**

1. 如果项目中有自定义的 `SlimVueBridgeInterface` 实现类，更新方法签名以匹配新的类型声明
2. 确保调用方传入的参数类型正确——传入非预期类型将触发 `TypeError`

### `TwigBridgeInfo` 内部变更

- 使用 constructor promotion（`private array $data`）
- 所有方法添加了参数和返回类型声明
- `getPlainValue()` 内部实现变更（不影响外部行为）

**迁移步骤：**

如果项目仅通过 `SlimVueBridgeInterface` 使用 `TwigBridgeInfo`，无需额外操作。如果直接访问了内部属性或方法，需要适配新的类型声明。

### Silex → `oasis/http` 替换

`index.php`（开发服务器示例）从 Silex 迁移到 `oasis/http`（`MicroKernel`）。

v3（Silex）：

```php
$app = new Silex\Application();
$app->register(new Silex\Provider\TwigServiceProvider(), [...]);
$app->get('/', function () use ($app) { ... });
$app->error(function (\Exception $e, $code) { ... });
$app->run();
```

v4（oasis/http）：

```php
$config = [
    'routing'        => ['path' => 'routes.yml', 'namespaces' => [...]],
    'twig'           => ['template_dir' => __DIR__ . '/templates'],
    'error_handlers' => [new DemoErrorHandler()],
];
$kernel = new MicroKernel($config, isDebug: true);
$kernel->run();
```

核心差异：

| 功能 | Silex | oasis/http |
|------|-------|------------|
| 路由 | 闭包式 `$app->get(...)` | Controller 类 + YAML 路由文件 |
| Twig 集成 | ServiceProvider 注册 | bootstrap config 的 `twig` 键 |
| 错误处理 | `$app->error()` 方法 | config 的 `error_handlers` 键 |
| 依赖注入 | `$app['twig']` 容器访问 | Controller 方法参数类型提示自动注入 |

**迁移步骤：**

1. 创建 Controller 类，将路由闭包中的逻辑迁移到 Controller 方法
2. 创建 `routes.yml` 定义路由映射
3. 将 `$app->error()` 回调迁移到实现 `__invoke(\Throwable $e, Request $request, int $code)` 的错误处理类
4. 更新 `index.php` 使用 `MicroKernel` 启动
5. 在 `composer.json` 的 `autoload-dev` 中注册 Controller 命名空间

### PHP 命令约定变更

| 项目 | v3 | v4 |
|------|-----|-----|
| PHP 命令 | `php74`（alias） | `php`（系统默认） |

所有文档和脚本中的 `php74` 引用已替换为 `php`。

---

## Frontend Breaking Changes

### Vue 2 → Vue 3

| 项目 | v3 | v4 |
|------|-----|-----|
| Vue.js | ^2.6 | ^3.5 |
| `vue-template-compiler` | 需要 | 已移除（Vue 3 内置 `@vue/compiler-sfc`） |

**迁移步骤：**

1. 更新 `package.json` 中 `vue` 版本为 `^3`
2. 移除 `vue-template-compiler`
3. 按下方 `slimvue.js` API 变更 section 更新代码

### webpack (Vue CLI) → Vite

| 项目 | v3 | v4 |
|------|-----|-----|
| 构建工具 | Vue CLI 4 (webpack) | Vite 8 |
| 配置文件 | `vue.config.js` | `vite.config.js` |
| 构建辅助模块目录 | `build/` | `scripts/` |
| 入口扫描器 | `build/entries.js`（CommonJS） | `scripts/entries.js`（ESM） |
| TDK 注入 | `build/tdk.js` + HtmlWebpackPlugin | `scripts/tdk.js` + Vite `transformIndexHtml` 插件 |
| Node 版本检查 | 无 | `scripts/check-node.js`（要求 >= 24） |
| 模块格式 | CommonJS | ESM（`"type": "module"`） |

已移除的文件和目录：

- `vue.config.js`
- `babel.config.js`
- `jest.config.js`
- `.eslintrc.js`
- 整个 `build/` 目录（含 `config.js`、`devServer.js`、`entries.js`、`tdk.js`、`webpack-modify/`、`webpack-add/` 等）

**迁移步骤：**

1. 删除 `vue.config.js`、`babel.config.js`、`jest.config.js`、`.eslintrc.js`
2. 删除整个 `build/` 目录
3. 创建 `vite.config.js`（示例见下方）
4. 将 `build/entries.js` 中的自定义入口扫描逻辑迁移到 `scripts/entries.js`（ESM 格式）
5. 将 `build/tdk.js` 中的 TDK 逻辑迁移到 `scripts/tdk.js`（使用 Vite `transformIndexHtml` 钩子）
6. 在 `package.json` 中添加 `"type": "module"`

`vite.config.js` 参考模板：

```javascript
import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import { resolve } from 'path';
import { scanEntries } from './scripts/entries.js';
import { tdkPlugin, defaultTdkMap } from './scripts/tdk.js';
import { checkNodeVersion } from './scripts/check-node.js';

checkNodeVersion(24);

export default defineConfig(({ mode: _mode }) => {
    const { inputMap } = scanEntries({ tdkMap: defaultTdkMap });

    return {
        plugins: [vue(), tdkPlugin(defaultTdkMap)],
        resolve: {
            alias: {
                '@': resolve(__dirname, 'src'),
                slimvue: resolve(__dirname, 'slimvue.js'),
                assets: resolve(__dirname, 'src/assets'),
            },
        },
        build: {
            rolldownOptions: {
                input: inputMap,
            },
            outDir: process.env.OUTPUT_DIR || 'dist',
        },
        base: process.env.PUBLIC_PATH || '/',
        test: {
            environment: 'jsdom',
            globals: true,
            coverage: {
                provider: 'v8',
                include: ['src/**/*.{js,vue}', 'slimvue.js', 'scripts/**/*.js'],
                reporter: ['text', 'lcov', 'clover'],
            },
        },
    };
});
```

根据项目实际情况调整 `alias`、`build.rollupOptions.input` 和 `scripts/` 下的辅助模块。

### npm 依赖变更

已移除的依赖：

| 依赖 | 说明 |
|------|------|
| `core-js` | Vue 3 不再需要 |
| `@vue/cli-service` | 被 Vite 替代 |
| `@vue/cli-plugin-babel` | 被 Vite 替代 |
| `@vue/cli-plugin-eslint` | 被 Vite 替代 |
| `babel-core` | 被 Vite 替代 |
| `babel-eslint` | 被 Vite 替代 |
| `babel-jest` | 被 Vitest 替代 |
| `vue-template-compiler` | Vue 3 内置编译器 |
| `vue-jest` | 被 Vitest 替代 |
| `sass-loader` | Vite 原生支持 Sass |

新增的依赖：

| 依赖 | 版本 | 说明 |
|------|------|------|
| `vite` | ^8 | 构建工具 |
| `@vitejs/plugin-vue` | ^6 | Vite Vue 插件 |
| `vitest` | ^4 | 测试框架 |
| `@vue/test-utils` | ^2.4 | Vue 3 测试工具 |
| `@vitest/coverage-v8` | ^4 | 覆盖率工具 |
| `fast-check` | ^4 | Property-Based Testing |
| `jsdom` | ^29 | 测试环境 |
| `eslint` | ^10 | ESLint（flat config） |
| `@eslint/js` | ^10 | ESLint 核心规则包 |
| `eslint-plugin-vue` | ^10 | Vue 3 ESLint 插件 |
| `eslint-config-prettier` | ^10 | Prettier 兼容 |
| `prettier` | ^3.6 | 代码格式化 |

**迁移步骤：**

1. 移除上述已废弃的依赖
2. 添加新依赖
3. 运行 `npm install`

### `package.json` scripts 变更

v3 脚手架生成的 scripts 与 v4 的对应关系如下。注意 v3 → v4 不仅是值的替换，还涉及 **key 重命名**、**新增** 和 **删除**：

| v3 key | v3 值 | v4 key | v4 值 | 变更类型 |
|--------|-------|--------|-------|----------|
| `serve` | `vue-cli-service serve --mode serve` | `dev` | `vite` | 重命名 + 替换值 |
| `build` | `vue-cli-service build --mode development` | `build` | `node scripts/check-node.js && vite build --mode development` | 替换值 |
| `release` | `vue-cli-service build --modern` | `release` | `node scripts/check-node.js && vite build` | 替换值 |
| — | — | `preview` | `vite preview` | 新增 |
| `lint` | `vue-cli-service lint` | `lint` | `eslint .` | 替换值 |
| — | — | `test` | `vitest run` | 新增 |
| — | — | `test:watch` | `vitest` | 新增 |
| — | — | `test:coverage` | `vitest run --coverage` | 新增 |
| `watch` | `vue-cli-service build --mode development --watch` | — | — | 删除 |
| `lib` | `vue-cli-service build --target lib ...` | — | — | 删除 |
| `inspect` | `vue-cli-service inspect` | — | — | 删除 |

> 如果你的 v3 项目中有 Jest 相关 scripts（如 `"test": "jest --no-cache"`），同样需要替换为 Vitest 命令。

**迁移步骤：**

将 `package.json` 的 `scripts` 字段**整体替换**为以下内容（而非逐条修改，避免遗漏）：

```json
"scripts": {
    "dev": "vite",
    "build": "node scripts/check-node.js && vite build --mode development",
    "release": "node scripts/check-node.js && vite build",
    "preview": "vite preview",
    "lint": "eslint .",
    "test": "vitest run",
    "test:watch": "vitest",
    "test:coverage": "vitest run --coverage"
}
```

删除 v3 中已废弃的 scripts（`serve`、`watch`、`lib`、`inspect`）。如果项目有自定义 scripts（非脚手架生成的），按需保留。

### Node.js 版本约束

| 项目 | v3 | v4 |
|------|-----|-----|
| Node.js 最低版本 | 无约束 | >= 24 |

v4 在 `package.json` 中声明 `"engines": { "node": ">=24" }`，并在 `build` 和 `release` scripts 中通过 `scripts/check-node.js` 强制检查。

**迁移步骤：**

1. 升级 Node.js 到 24+
2. 在 `package.json` 中添加 `engines` 字段

### Jest → Vitest

| 项目 | v3 | v4 |
|------|-----|-----|
| 测试框架 | Jest（如项目使用） | Vitest ^4 |
| 配置文件 | `jest.config.js` | `vite.config.js` 内 `test` 字段 |
| Mock API | `jest.fn()` / `jest.spyOn()` | `vi.fn()` / `vi.spyOn()` |
| 测试环境 | jsdom（Jest 内置） | jsdom（需显式配置） |

**迁移步骤：**

1. 删除 `jest.config.js`
2. 在 `vite.config.js` 中添加 `test` 配置：
   ```javascript
   test: {
       environment: 'jsdom',
       globals: true,
       coverage: {
           provider: 'v8',
           include: ['src/**/*.{js,vue}', 'slimvue.js', 'scripts/**/*.js'],
           reporter: ['text', 'lcov', 'clover'],
       },
   },
   ```
3. 更新测试文件中的 API 调用：
   - `jest.fn()` → `vi.fn()`
   - `jest.spyOn()` → `vi.spyOn()`
   - `jest.mock()` → `vi.mock()`
   - `jest.useFakeTimers()` → `vi.useFakeTimers()`
   - `jest.clearAllMocks()` → `vi.clearAllMocks()`
4. 更新 `import` 语句（如需要从 `vitest` 导入 `describe`、`it`、`expect` 等）

### ESLint flat config 迁移

| 项目 | v3 | v4 |
|------|-----|-----|
| 配置文件 | `.eslintrc.js`（CommonJS，extends 模式） | `eslint.config.js`（ESM，flat config 数组模式） |
| Vue 插件 | `eslint-plugin-vue`（Vue 2 config） | `eslint-plugin-vue`（Vue 3 `flat/recommended`） |
| Prettier 集成 | `eslint-plugin-prettier` | `eslint-config-prettier` |
| 核心规则包 | 内置 | `@eslint/js`（需显式导入） |

v4 配置示例（简化版，完整版参见模板项目 `eslint.config.js`）：

```javascript
import js from '@eslint/js';
import pluginVue from 'eslint-plugin-vue';
import eslintConfigPrettier from 'eslint-config-prettier';

export default [
    { name: 'app/ignores', ignores: ['coverage/**', 'dist/**', 'node_modules/**'] },
    js.configs.recommended,
    ...pluginVue.configs['flat/recommended'],
    eslintConfigPrettier,
    {
        name: 'app/rules',
        rules: {
            'vue/multi-word-component-names': 'off',
            'no-unused-vars': ['error', { argsIgnorePattern: '^_' }],
            eqeqeq: ['error', 'always'],
            'no-var': 'error',
            'prefer-const': 'error',
        },
    },
];
```

**迁移步骤：**

1. 删除 `.eslintrc.js`
2. 安装 `@eslint/js`（v4.1+ 需要显式依赖）
3. 创建 `eslint.config.js`，使用 ESM flat config 格式
4. 将 `extends` 配置转换为数组展开形式
5. 更新 Vue 插件配置为 Vue 3 recommended
6. 为每个配置块添加 `name` 字段（便于调试）

### 环境变量前缀变更

| 项目 | v3 | v4 |
|------|-----|-----|
| Bridge 环境变量 | `VUE_APP_BRIDGE` | `VITE_BRIDGE` |
| 环境变量前缀 | `VUE_APP_*` | `VITE_*` |
| 环境检测 | `process.env.NODE_ENV` | `import.meta.env.MODE` |

**迁移步骤：**

1. 将 `.env` 文件中所有 `VUE_APP_` 前缀替换为 `VITE_`
2. 将代码中 `process.env.VUE_APP_*` 替换为 `import.meta.env.VITE_*`
3. 将 `process.env.NODE_ENV` 替换为 `import.meta.env.MODE`

---

## `slimvue.js` API Changes

### `mount()` 方法

v3：

```javascript
import Vue from 'vue';

mount(vueComponent) {
    const app = new Vue({ render: h => h(vueComponent) });
    Vue.config.productionTip = false;
    app.$mount('#app');
    window.slimvue = app;
    return app;
}
```

v4：

```javascript
import { createApp } from 'vue';

mount(vueComponent) {
    const app = createApp(vueComponent);
    app.config.globalProperties.$toCssUrl = (imageUrl) => `url(${imageUrl})`;
    app.config.globalProperties.$toCssBackgroundImage = (imageUrl) => ({
        backgroundImage: `url(${imageUrl})`,
    });
    app.mount('#app');
    window.slimvue = app;
    return app;
}
```

关键变更：

| 项目 | v3 | v4 |
|------|-----|-----|
| 创建实例 | `new Vue({ render: h => h(component) })` | `createApp(component)` |
| 挂载 | `app.$mount('#app')` | `app.mount('#app')` |
| 全局属性 | `Vue.prototype.$xxx = ...`（在 `mount()` 外部） | `app.config.globalProperties.$xxx = ...`（在 `mount()` 内部） |
| `productionTip` | `Vue.config.productionTip = false` | 已移除（Vue 3 无此配置） |
| 返回值 | Vue 2 实例 | Vue 3 App 实例 |

**迁移步骤：**

1. 将 `import Vue from 'vue'` 替换为 `import { createApp } from 'vue'`
2. 将 `new Vue({ render: h => h(component) }).$mount('#app')` 替换为 `createApp(component).mount('#app')`
3. 将 `Vue.prototype.$xxx = ...` 移入 `mount()` 内部，改为 `app.config.globalProperties.$xxx = ...`
4. 移除 `Vue.config.productionTip = false`

### `bridge` getter

v3：

```javascript
get bridge() {
    if (typeof window.bridge === 'undefined') {
        return JSON.parse(process.env.VUE_APP_BRIDGE);
    }
    return window.bridge;
}
```

v4：

```javascript
get bridge() {
    if (typeof window.bridge === 'undefined') {
        return JSON.parse(import.meta.env.VITE_BRIDGE);
    }
    return window.bridge;
}
```

### `isDebug` getter

v3：

```javascript
get isDebug() {
    return process.env.NODE_ENV !== 'production';
}
```

v4：

```javascript
get isDebug() {
    return import.meta.env.MODE !== 'production';
}
```

---

## CLI Command Changes

### `bin/slimvue` 版本

| 项目 | v3 | v4 |
|------|-----|-----|
| CLI 版本号 | `1.4` | `4.0` |

### `initialize` 命令变更

- 输出消息中 `npm run serve` 已替换为 `npm run dev`
- 模板复制时排除 `build/`、`vue.config.js`、`babel.config.js`、`jest.config.js`（这些文件在 v4 模板中已不存在）
- 生成的项目为 Vite 项目结构（含 `vite.config.js`、`scripts/` 目录）

### `upgrade` 命令变更

**新增行为 — 字段缺失检测：**

v4 的 `upgrade` 命令在执行前会检查目标项目 `package.json` 中的必需字段。当以下任一字段缺失时，命令中止并报错：

- `name`
- `version`
- `dependencies`
- `devDependencies`

错误消息示例：

```
Missing required field 'name' in package.json. Please fix manually and retry.
```

**新增行为 — 过时文件清理：**

`upgrade` 命令执行后会自动移除目标目录中的过时文件：

- `build/`
- `vue.config.js`
- `babel.config.js`
- `jest.config.js`
- `.eslintrc.js`

**迁移步骤：**

1. 确保目标项目的 `package.json` 包含 `name`、`version`、`dependencies`、`devDependencies` 四个字段
2. 运行 `bin/slimvue upgrade <project-dir>` 后，检查 git diff 确认变更

---

## Vue 3 Component Migration

v4 的模板组件全部重新设计，从 Vue 2 Options API 迁移到 Vue 3 Composition API + `<script setup>`。

### 组件语法变更

v3（Options API）：

```vue
<script>
export default {
    props: { msg: String },
    data() { return { count: 0 }; },
    computed: { doubled() { return this.count * 2; } },
    methods: { increment() { this.count++; } },
};
</script>
```

v4（Composition API + `<script setup>`）：

```vue
<script setup>
import { ref, computed } from 'vue';

const props = defineProps({ msg: { type: String, required: true } });
const emit = defineEmits(['greet']);
const count = ref(0);
const doubled = computed(() => count.value * 2);
function increment() { count.value++; }
</script>
```

### 关键 API 映射

| Vue 2 | Vue 3 |
|-------|-------|
| `export default { ... }` | `<script setup>` |
| `props: { ... }` | `defineProps({ ... })` |
| `$emit('event')` | `const emit = defineEmits([...]); emit('event')` |
| `data() { return { x: 0 } }` | `const x = ref(0)` |
| `computed: { foo() {} }` | `const foo = computed(() => ...)` |
| `watch: { x(val) {} }` | `watch(x, (val) => { ... })` |
| `this.$xxx` | `app.config.globalProperties.$xxx`（通过 `getCurrentInstance`） |
| Mixins | Composables（`use*` 函数） |

### Composables

v4 引入 composables 模式替代 mixins。可复用逻辑提取为 `src/composables/use*.js` 函数：

```javascript
// src/composables/useClock.js
import { ref, computed, onMounted, onUnmounted } from 'vue';

export function useClock() {
    const time = ref(Date.now());
    let timer = null;
    onMounted(() => { timer = setInterval(() => { time.value = Date.now(); }, 1000); });
    onUnmounted(() => { clearInterval(timer); });
    return { time, fullDateTime: computed(() => new Date(time.value).toLocaleString()) };
}
```

**迁移步骤：**

1. 将所有组件从 Options API 重写为 `<script setup>` + Composition API
2. 将 `props` 声明替换为 `defineProps()`
3. 将 `$emit` 替换为 `defineEmits()`
4. 将 `data()` 替换为 `ref()` / `reactive()`
5. 将 `computed` 替换为 `computed()`
6. 将 mixins 提取为 composables
7. 更新所有 entry 文件使用 `slimvue.mount(Component)` API

---

## Migration Tools

SlimVue v4 提供两个 CLI 工具辅助迁移，可自动完成部分步骤并检测迁移完成度：

- `vendor/bin/slimvue-migrate <project-dir>` — 自动执行可自动化的迁移步骤（composer.json 更新、过时文件清理、Vue 2→3 代码转换、npm scripts 替换）
- `vendor/bin/slimvue-migrate-check <project-dir>` — 检测项目的 v4 迁移完成度，不修改任何文件

详细用法参见 `docs/manual/migration-tools.md`。

---

## Summary Checklist

迁移完成后，使用以下清单验证：

- [ ] PHP >= 8.5 已安装
- [ ] `composer.json` 依赖已更新，`php $(which composer) install` 成功
- [ ] `silex/silex` 已替换为 `oasis/http`
- [ ] PHP 源码中无 Silex 类引用
- [ ] `SlimVueBridgeInterface` 实现类已适配类型声明
- [ ] Node.js >= 24 已安装
- [ ] `package.json` 依赖已更新，`npm install` 成功
- [ ] `package.json` 已添加 `"type": "module"`
- [ ] `package.json` scripts 已按 v4 标准整体替换（含 `release`、`preview`、`test:watch` 等）
- [ ] v3 废弃的 scripts（`serve`、`watch`、`lib`、`inspect`）已删除
- [ ] `vue.config.js`、`babel.config.js`、`jest.config.js`、`.eslintrc.js`、`build/` 已删除
- [ ] `vite.config.js` 已创建
- [ ] `eslint.config.js`（flat config）已创建
- [ ] 所有 `VUE_APP_*` 环境变量已替换为 `VITE_*`
- [ ] `slimvue.js` 已迁移到 Vue 3 API（`createApp`、`globalProperties`）
- [ ] 所有组件已迁移到 `<script setup>` + Composition API
- [ ] 所有测试已从 Jest 迁移到 Vitest
- [ ] `npm run build` 构建成功
- [ ] `npm run release` 构建成功
- [ ] `npm run lint` 无错误
- [ ] `npm run test` 全部通过
