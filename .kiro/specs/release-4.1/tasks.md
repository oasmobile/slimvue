# Implementation Plan: Frontend Toolchain Upgrade (v4.1)

## Overview

基于 requirements.md（7 条 Requirement）和 design.md 的技术方案，将升级工作拆分为可执行的编码任务。执行策略：lock 文件先删除重生成 → 构建链（Vite 8 + Vitest 4）→ 测试代码适配 → Lint 链（ESLint 10）→ 其余小版本 → Composer 依赖 → State 文档更新。

Design CR 关键决策：
- 每个批次作为一个 task（粗粒度）
- 测试代码适配作为独立 task
- lock 文件在批次 1 开始前删除重生成，state 文档在最后更新
- 小修复立即处理；大修复创建独立 task 但仍在本次 release 中完成

## Tasks

- [-] 1. 删除 lock 文件并重新生成
  - [x] 1.1 删除 `slimvue-template/package-lock.json`
    - 删除现有 lock 文件
    - _Requirements: 6.1_
  - [x] 1.2 执行 `npm install` 重新生成 lock 文件
    - 在 `slimvue-template/` 下运行 `npm install`
    - 确认无依赖解析错误
    - 确认生成了新的 `package-lock.json`
    - _Requirements: 6.2_
  - [x] 1.3 执行 `npm audit` 安全审计
    - 运行 `npm audit`，确认零 critical/high 漏洞
    - 如有漏洞，尝试升级相关依赖解决；如无修复版本，记录为已知风险
    - _Requirements: 6.3_
  - [-] 1.4 Checkpoint — lock 文件重新生成完成
    - 确认 `package-lock.json` 存在且 `npm install` 无错误
    - 通过后 commit
    - 如有问题请向用户确认

- [~] 2. 批次 1：构建链升级（Vite 8 + Vitest 4 + @vitest/coverage-v8）
  - [ ] 2.1 更新 `package.json` 构建链依赖版本
    - 将 `vite` 从 `^7` 升级到 `^8`
    - 将 `vitest` 从 `^3` 升级到 `^4`
    - 将 `@vitest/coverage-v8` 从 `^3.2.4` 升级到 `^4`
    - 运行 `npm install` 更新依赖
    - _Requirements: 1.1, 1.2, 1.3_
  - [ ] 2.2 适配 `vite.config.js` — breaking changes 修复 + 推荐写法
    - 将 `build.rollupOptions` 重命名为 `build.rolldownOptions`（Vite 8 breaking change，同时也是 Vite 8 推荐写法）
    - _Requirements: 2.1, 2.2_
  - [ ] 2.3 适配 `vite.config.js` — Vitest 4 推荐写法
    - 在 `test.coverage` 中添加 `include: ['src/**/*.{js,vue}', 'slimvue.js', 'scripts/**/*.js']`（Vitest 4 推荐显式声明）
    - _Requirements: 2.3, 2.4_
  - [ ] 2.4 验证构建链升级
    - 运行 `npm run test`，确认所有测试通过（如有失败立即修复）
    - 运行 `npm run build`，确认构建产出正常
    - 运行 `npm run release`，确认生产构建正常
    - _Requirements: 1.4, 1.5, 1.6, 2.5, 2.6_
  - [ ] 2.5 Checkpoint — 构建链升级完成
    - 确认 test/build/release 全部通过
    - 通过后 commit
    - 如有问题请向用户确认

- [ ] 3. 测试代码适配
  - [ ] 3.1 更新 `tests/build-config.test.js`
    - 检查测试文件中是否有对 `rollupOptions` 的字符串断言，如有则更新为 `rolldownOptions`
    - 检查是否有其他因 Vite 8 / Vitest 4 变更而需要适配的断言
    - _Requirements: 2.5_
  - [ ] 3.2 检查并修复其他测试文件
    - 逐一运行各测试文件，确认无因 Vitest 4 行为变更（如 `vi.restoreAllMocks` 行为变更）导致的失败
    - 如有失败立即修复
    - _Requirements: 1.4, 2.5_
  - [ ] 3.3 Checkpoint — 测试代码适配完成
    - 运行 `npm run test`，确认全部通过
    - 通过后 commit
    - 如有问题请向用户确认

- [ ] 4. 批次 2：Lint 链升级（ESLint 10）
  - [ ] 4.1 更新 `package.json` ESLint 版本
    - 将 `eslint` 从 `^9` 升级到 `^10`
    - 运行 `npm install` 更新依赖
    - _Requirements: 3.1_
  - [ ] 4.2 适配 `eslint.config.js` — ESLint 10 推荐写法
    - 为各配置块添加 `name` 字段（`slimvue/ignores`、`slimvue/base-rules`、`slimvue/node-scripts`、`slimvue/browser-source`、`slimvue/test-files`）
    - 检查源码中是否有 `/* eslint-env */` 注释，如有则移除
    - _Requirements: 4.1, 4.2_
  - [ ] 4.3 验证 Lint 链升级
    - 运行 `npm run lint`，确认零新增错误且无运行时异常
    - 如有新规则触发的报错，立即修复代码或调整规则配置
    - _Requirements: 3.2, 3.3, 4.3, 4.4_
  - [ ] 4.4 Checkpoint — Lint 链升级完成
    - 确认 lint 通过
    - 通过后 commit
    - 如有问题请向用户确认

- [ ] 5. 其余小版本依赖更新
  - [ ] 5.1 更新 `package.json` 中其余依赖版本
    - 确认 `@vitejs/plugin-vue` 为最新 ^6 小版本
    - 确认 `eslint-config-prettier` 为最新 ^10 小版本
    - 确认 `eslint-plugin-vue` 为最新 ^10 小版本
    - 确认 `@vue/test-utils` 为最新 ^2 小版本
    - 确认 `prettier` 为最新 ^3 小版本
    - 确认 `jsdom` 为最新 ^29 小版本
    - 确认 `fast-check` 为最新 ^4 小版本
    - 确认 `sass` 为最新 ^1 小版本
    - 确认 `autoprefixer` 为最新 ^10 小版本
    - 运行 `npm install` 更新依赖（lock 文件会自动更新）
    - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5, 5.6, 5.7, 5.8, 5.9_
  - [ ] 5.2 验证小版本更新后的整体状态
    - 运行 `npm run test`，确认全部通过
    - 运行 `npm run build`，确认构建正常
    - 运行 `npm run lint`，确认零新增错误
    - 如有兼容性问题，仍保持最新版本并作为 bug 修复处理（CR Q3 决策）
    - _Requirements: 5.10, 5.11, 5.12_
  - [ ] 5.3 Checkpoint — 小版本依赖更新完成
    - 确认 test/build/lint 全部通过
    - 通过后 commit
    - 如有问题请向用户确认

- [ ] 6. Composer 依赖更新
  - [ ] 6.1 运行 `composer update` 更新 PHP 依赖
    - 运行 `composer update`，将所有依赖更新到约束范围内的最新版本
    - 确认无依赖解析错误
    - 提交更新后的 `composer.lock`
    - _Requirements: Design — PHP 依赖更新_
  - [ ] 6.2 验证 PHP 测试和静态分析
    - 运行 `vendor/bin/phpunit`，确认所有 PHP 测试通过
    - 运行 `vendor/bin/phpstan analyse`，确认静态分析通过
    - 如有问题立即修复
    - _Requirements: Design — PHP 依赖更新_
  - [ ] 6.3 Checkpoint — Composer 依赖更新完成
    - 确认 phpunit 和 phpstan 全部通过
    - 通过后 commit
    - 如有问题请向用户确认

- [ ] 7. State 文档更新
  - [ ] 7.1 更新 `docs/state/architecture.md` 技术选型表
    - 将构建工具行从 `Vite | ^7` 更新为 `Vite（Rolldown）| ^8`
    - 将前端测试行从 `Vitest | ^3` 更新为 `Vitest | ^4`
    - 将代码规范行从 `ESLint（flat config）+ Prettier | ^9 / ^3.6` 更新为 `ESLint（flat config）+ Prettier | ^10 / ^3`
    - _Requirements: 7.1, 7.2, 7.3_
  - [ ] 7.2 更新 `docs/state/architecture.md` 工具描述
    - 检查文档中是否有提及 esbuild 或 Rollup 作为 Vite 打包器的描述
    - 如有，更新为 Rolldown（Vite 8 统一打包器）
    - 如无相关描述，此条件不成立，AC 自动视为通过（CR Q1 决策）
    - _Requirements: 7.4, 7.5_
  - [ ] 7.3 Checkpoint — State 文档更新完成
    - Review 文档一致性：确认版本号和描述与实际代码一致
    - 通过后 commit
    - 如有问题请向用户确认

- [ ] 8. 手工测试
  - [ ] 8.1 Increment alpha tag
    - 查询已有 alpha tag，取最大序号 +1 打新 tag
  - [ ] 8.2 前端构建链集成验证
    - [脚本] 在 `slimvue-template/` 下运行 `npm install`，确认无错误
    - [脚本] 运行 `npm run test`，确认全部通过
    - [脚本] 运行 `npm run build`，确认构建产出在 `dist/` 下
    - [脚本] 运行 `npm run release`，确认生产构建正常
    - [脚本] 运行 `npm run lint`，确认零错误
    - [脚本] 运行 `npm audit`，确认零 critical/high 漏洞
    - _Requirements: 1.4, 1.5, 1.6, 2.5, 2.6, 3.2, 3.3, 4.3, 4.4, 5.10, 5.11, 5.12, 6.2, 6.3_
  - [ ] 8.3 PHP 端集成验证
    - [脚本] 运行 `php $(which composer) install`，确认无错误
    - [脚本] 运行 `vendor/bin/phpunit`，确认全部通过
    - [脚本] 运行 `vendor/bin/phpstan analyse`，确认静态分析通过
    - _Requirements: Design — PHP 依赖更新_
  - [ ] 8.4 版本声明验证
    - [脚本] 读取 `package.json`，确认各包版本符合目标（vite ^8、vitest ^4、@vitest/coverage-v8 ^4、eslint ^10）
    - [脚本] 读取 `docs/state/architecture.md`，确认版本号已更新
    - _Requirements: 1.1, 1.2, 1.3, 3.1, 5.1–5.9, 7.1, 7.2, 7.3_
  - [ ] 8.5 Checkpoint — 手工测试完成
    - 汇总所有测试结果
    - 通过后 commit
    - 如有问题请向用户确认

- [ ] 9. Code Review
  - 委托给 `code-reviewer` sub-agent 执行
  - 基于当前分支的 diff 进行全面 code review

## Notes

- 执行时须遵循 `.kiro/steering/spec/spec-execution.md` 中的规范
- Commit 随 checkpoint 一起执行——每个 checkpoint 通过后进行一次 commit
- 问题处理策略：任何时候发现的问题，不论是不是新引入的，都应该立即处理；小修复立即处理，大修复创建独立 task 但仍在本次 release 中完成
- lock 文件在所有升级之前删除重生成（Task 1），确保后续升级基于干净的依赖树
- 本次升级不适用 property-based testing（所有变更为声明性配置，验证方式为集成测试）
- 所有 tasks 均为 mandatory，无 optional 标记
- 每个 top-level task 的最后一个 sub-task 为 Checkpoint

---

## Issues

（暂无。Stabilize 阶段新发现的 issue 记录于此。）

---

## Socratic Review

### Round 1 — Self Review

**Q1: 所有 7 条 Requirement 是否都有对应的 task 覆盖？**

逐条检查：
- Req 1（构建链升级）→ Task 2.1, 2.4 ✓
- Req 2（构建链配置适配）→ Task 2.2, 2.3, 2.4 ✓
- Req 3（Lint 链升级）→ Task 4.1, 4.3 ✓
- Req 4（Lint 链配置适配）→ Task 4.2, 4.3 ✓
- Req 5（其余小版本依赖更新）→ Task 5.1, 5.2 ✓
- Req 6（Lock 文件重新生成）→ Task 1.1, 1.2, 1.3 ✓
- Req 7（State 文档更新）→ Task 7.1, 7.2 ✓

**Q2: Design CR 的 5 项决策是否都已体现？**

- Q1（每个批次作为一个 task）→ Task 2 = 构建链批次，Task 4 = Lint 链批次 ✓
- Q2（测试代码适配作为独立 task）→ Task 3 ✓
- Q3（lock 在批次 1 前删除重生成，state 最后更新）→ Task 1 在 Task 2 之前，Task 7 在最后 ✓
- Q4（小修复立即处理，大修复独立 task）→ Notes 中明确说明 ✓
- Q5（Composer 依赖更新独立 task）→ Task 6 ✓

**Q3: Release spec 结构约束是否满足？**

- 实现 task（1–7）→ 手工测试（8）→ Code Review（9）✓
- 手工测试第一个 sub-task 为 "Increment alpha tag"（8.1）✓
- 所有 tasks 均为 mandatory，无 optional 标记 ✓
- 每个 top-level task 最后一个 sub-task 为 Checkpoint ✓
- Notes 提到执行时遵循 spec-execution.md ✓

**Q4: 是否存在孤立代码或未集成的步骤？**

- Task 1 重生成 lock → Task 2 升级构建链依赖时 lock 自动更新 → 无断裂 ✓
- Task 2 修改 vite.config.js → Task 3 适配测试代码 → 顺序合理 ✓
- Task 5 小版本更新后 lock 自动更新 → 无需再次重生成 ✓
- Task 6 Composer 独立于前端 → 无依赖关系冲突 ✓

**Q5: 验证覆盖是否完整？**

- `npm run test` → Task 2.4, 3.2, 5.2, 8.2 ✓
- `npm run build` / `npm run release` → Task 2.4, 5.2, 8.2 ✓
- `npm run lint` → Task 4.3, 5.2, 8.2 ✓
- `npm audit` → Task 1.3, 8.2 ✓
- 版本声明验证 → Task 8.4 ✓
- State 文档验证 → Task 7.3, 8.4 ✓
- PHP 验证 → Task 6.2, 8.3 ✓

---

## Gatekeep Log

**校验时间**: 2025-07-15
**校验结果**: ⚠️ 已修正后通过

### 修正项

- [结构] 补充 `## Issues` section（release spec 要求，stabilize 阶段记录新发现 issue 的位置）
- [内容] Task 2.2 的 requirement 引用补充 2.2（`rollupOptions` → `rolldownOptions` 同时覆盖 breaking change 修复和推荐写法采用）

### 合规检查

- [x] 无 TBD / TODO / 待定 / 占位符
- [x] 无空 section 或不完整的列表
- [x] 内部引用一致（requirement 编号、design 模块名）
- [x] checkbox 语法正确（`- [ ]`）
- [x] 无 markdown 格式错误
- [x] `## Tasks` section 存在
- [x] Release spec 结构：实现 task → 手工测试 → Code Review
- [x] 手工测试第一个 sub-task 为 "Increment alpha tag"
- [x] Code Review 是最后一个 top-level task
- [x] top-level task 有序号，sub-task 有层级序号，序号连续
- [x] 每个实现类 sub-task 引用了 requirements 条款
- [x] requirements.md 中每条 requirement 至少被一个 task 引用
- [x] 引用的 requirement 编号在 requirements.md 中存在
- [x] top-level task 按依赖关系排序，无循环依赖
- [x] Graphify 校验：图中无前端配置文件节点，task 排序基于逻辑依赖，无隐含跨模块依赖遗漏
- [x] Checkpoint 作为每个 top-level task 的最后一个 sub-task
- [x] Checkpoint 包含具体验证命令和 commit 动作
- [x] 每个 sub-task 足够具体，可独立执行
- [x] 无过粗或过细的 task
- [x] 所有 tasks 均为 mandatory，无 optional
- [x] 手工测试覆盖关键用户场景
- [x] Code Review 委托给 code-reviewer sub-agent
- [x] `## Notes` section 存在
- [x] Notes 提到遵循 spec-execution.md
- [x] Notes 说明 commit 随 checkpoint 执行
- [x] Notes 包含 spec 特有执行要点（问题处理策略、lock 时机、不适用 PBT）
- [x] `## Issues` section 存在（release spec 要求）
- [x] Socratic Review 存在且覆盖充分
- [x] Design CR 5 项决策全部在 tasks 编排中体现
- [x] Design 全覆盖（所有模块和实现项有对应 task）
- [x] 可独立执行（sub-task 描述自包含）
- [x] 验收闭环（checkpoint + 手工测试 + code review）
- [x] 执行路径无歧义（排序和依赖关系清晰）
