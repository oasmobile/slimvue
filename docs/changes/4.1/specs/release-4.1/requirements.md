# Requirements Document

前端工具链大版本升级（v4.1）的需求规格，覆盖构建链（Vite 8 + Vitest 4）、Lint 链（ESLint 10）、其余小版本依赖更新三个批次。

---

## Introduction

SlimVue 的前端工具链在 v4.0 中已升级到 Vite 7 + Vitest 3 + ESLint 9。自那次升级以来，三个核心工具发布了新的大版本：Vite 8（Rolldown 统一打包器取代 esbuild + Rollup 双引擎）、Vitest 4（适配 Vite 8）、ESLint 10。其余依赖也有小版本更新可跟进。

本次 release 4.1 是一次纯工具链升级，不引入新功能特性，不变更项目架构或目录结构，不涉及 PHP 端任何变更。

**不涉及的内容（Non-scope）：**

- 不升级 Node.js 最低版本要求（保持 `>=24`）
- 不升级 Vue 大版本（仍为 Vue 3.5）
- 不引入新功能特性
- 不变更项目架构或目录结构
- 不做 PHP 端任何变更

---

## Glossary

- **Template_Project**: `slimvue-template/` 目录下的前端项目模板
- **Build_System**: 前端构建系统，基于 Vite + Rolldown
- **Lint_Chain**: 代码规范工具集合，包含 ESLint 及其插件
- **State_Doc**: `docs/state/` 目录下的系统事实来源文档
- **Lock_File**: `slimvue-template/package-lock.json`，npm 依赖锁定文件

---

## Requirements

### Requirement 1: 构建链升级（Vite 8 + Vitest 4 + @vitest/coverage-v8）

**User Story:** 作为库维护者，我希望将 Vite 升级到 ^8、Vitest 升级到 ^4、@vitest/coverage-v8 同步升级到 ^4，以便构建系统使用 Rolldown 统一打包器并保持测试框架与构建工具的版本兼容。

#### Acceptance Criteria

1. THE Template_Project SHALL declare `vite` ^8 in `package.json` `devDependencies`
2. THE Template_Project SHALL declare `vitest` ^4 in `package.json` `devDependencies`
3. THE Template_Project SHALL declare `@vitest/coverage-v8` ^4 in `package.json` `devDependencies`
4. WHEN `npm run test` is executed in `slimvue-template/`, THE Template_Project SHALL report all existing tests passing with zero failures
5. WHEN `npm run build` is executed in `slimvue-template/`, THE Build_System SHALL produce output in `dist/` without errors
6. WHEN `npm run release` is executed in `slimvue-template/`, THE Build_System SHALL produce output in `dist/` without errors

### Requirement 2: 构建链配置适配

**User Story:** 作为库维护者，我希望 `vite.config.js` 和 Vitest 相关配置适配 Vite 8 / Vitest 4 的 breaking changes 并采用新推荐写法，以便配置文件符合最新版本的最佳实践。

#### Acceptance Criteria

1. WHEN Vite 8 引入了 breaking configuration changes, THE Template_Project SHALL update `vite.config.js` to resolve all breaking changes
2. WHEN Vite 8 提供了新推荐写法, THE Template_Project SHALL adopt the new recommended patterns in `vite.config.js`
3. WHEN Vitest 4 引入了 breaking configuration changes, THE Template_Project SHALL update the Vitest configuration section to resolve all breaking changes
4. WHEN Vitest 4 提供了新推荐写法, THE Template_Project SHALL adopt the new recommended patterns in the Vitest configuration
5. WHEN `npm run test` is executed after configuration adaptation, THE Template_Project SHALL report all tests passing with zero failures
6. WHEN `npm run build` is executed after configuration adaptation, THE Build_System SHALL produce output without errors

### Requirement 3: Lint 链升级（ESLint 10）

**User Story:** 作为库维护者，我希望将 ESLint 升级到 ^10，以便代码规范工具保持最新并与生态插件的后续版本兼容。

#### Acceptance Criteria

1. THE Template_Project SHALL declare `eslint` ^10 in `package.json` `devDependencies`
2. WHEN `npm run lint` is executed in `slimvue-template/`, THE Lint_Chain SHALL report zero new errors compared to the pre-upgrade baseline
3. WHEN `npm run lint` is executed in `slimvue-template/`, THE Lint_Chain SHALL complete without runtime exceptions

### Requirement 4: Lint 链配置适配

**User Story:** 作为库维护者，我希望 `eslint.config.js` 适配 ESLint 10 的 breaking changes 并采用新推荐写法，以便配置文件符合最新版本的最佳实践。

#### Acceptance Criteria

1. WHEN ESLint 10 引入了 breaking configuration changes, THE Template_Project SHALL update `eslint.config.js` to resolve all breaking changes
2. WHEN ESLint 10 提供了新推荐写法（如规则语言声明等）, THE Template_Project SHALL adopt the new recommended patterns in `eslint.config.js`
3. WHEN `npm run lint` is executed after configuration adaptation, THE Lint_Chain SHALL report zero new errors
4. WHEN `npm run lint` is executed after configuration adaptation, THE Lint_Chain SHALL complete without runtime exceptions

### Requirement 5: 其余小版本依赖更新

**User Story:** 作为库维护者，我希望将所有其余 npm 依赖升级到当前大版本线内的最新小版本，以便减少技术债累积并获得最新的 bug 修复和性能改进。

#### Acceptance Criteria

1. THE Template_Project SHALL declare `@vitejs/plugin-vue` at the latest ^6 minor version in `package.json` `devDependencies`
2. THE Template_Project SHALL declare `eslint-config-prettier` at the latest ^10 minor version in `package.json` `devDependencies`
3. THE Template_Project SHALL declare `eslint-plugin-vue` at the latest ^10 minor version in `package.json` `devDependencies`
4. THE Template_Project SHALL declare `@vue/test-utils` at the latest ^2 minor version in `package.json` `devDependencies`
5. THE Template_Project SHALL declare `prettier` at the latest ^3 minor version in `package.json` `devDependencies`
6. THE Template_Project SHALL declare `jsdom` at the latest ^29 minor version in `package.json` `devDependencies`
7. THE Template_Project SHALL declare `fast-check` at the latest ^4 minor version in `package.json` `devDependencies`
8. THE Template_Project SHALL declare `sass` at the latest ^1 minor version in `package.json` `devDependencies`
9. THE Template_Project SHALL declare `autoprefixer` at the latest ^10 minor version in `package.json` `devDependencies`
10. WHEN `npm run test` is executed after all minor version updates, THE Template_Project SHALL report all tests passing with zero failures
11. WHEN `npm run build` is executed after all minor version updates, THE Build_System SHALL produce output without errors
12. WHEN `npm run lint` is executed after all minor version updates, THE Lint_Chain SHALL report zero new errors

### Requirement 6: Lock 文件重新生成

**User Story:** 作为库维护者，我希望删除现有 lock 文件后重新生成，以便获得一份干净的、与所有新版本依赖一致的依赖树。

#### Acceptance Criteria

1. THE Template_Project SHALL delete the existing Lock_File before dependency installation
2. WHEN `npm install` is executed in `slimvue-template/` after deleting the Lock_File, THE Template_Project SHALL generate a new Lock_File without dependency resolution errors
3. WHEN `npm install` completes, THE Template_Project SHALL have zero `npm audit` critical or high severity vulnerabilities introduced by this upgrade

### Requirement 7: State 文档更新

**User Story:** 作为库维护者，我希望 `docs/state/` 中涉及版本号和工具描述的内容同步更新，以便系统事实来源文档反映升级后的实际状态。

#### Acceptance Criteria

1. THE State_Doc SHALL update the Vite version constraint from `^7` to `^8` in `docs/state/architecture.md`
2. THE State_Doc SHALL update the Vitest version constraint from `^3` to `^4` in `docs/state/architecture.md`
3. THE State_Doc SHALL update the ESLint version constraint from `^9` to `^10` in `docs/state/architecture.md`
4. WHEN `docs/state/architecture.md` contains references to esbuild or Rollup as Vite's bundler, THE State_Doc SHALL update those references to Rolldown
5. WHEN `docs/state/architecture.md` contains descriptions of Vite's build mechanism, THE State_Doc SHALL update those descriptions to reflect the Rolldown unified engine

---

## Socratic Review

### Round 1 — Self Review

**Q1: goal.md 中所有决策是否都已体现？**

- 执行顺序（分两批 + 小版本）→ 体现：Req 1/2 覆盖构建链，Req 3/4 覆盖 Lint 链，Req 5 覆盖其余小版本
- 配置适配（修复 breaking + 采用新推荐写法）→ 体现：Req 2 和 Req 4 分别覆盖构建链和 Lint 链的配置适配
- lock 文件（删除后重新生成）→ 体现：Req 6
- state 更新（版本号 + 相关工具描述同步更新）→ 体现：Req 7
- 验证标准（test green、build/release 成功、lint 无新增错误）→ 体现：Req 1 AC4/5/6、Req 2 AC5/6、Req 3 AC2/3、Req 4 AC3/4、Req 5 AC10/11/12

**Q2: 是否存在遗漏的 EARS 模式或不合规的 AC？**

逐条检查：所有 AC 均使用 THE/WHEN 开头，符合 EARS 模式。无 vague terms、无 pronouns、无 escape clauses。

**Q3: Non-Goals 是否被意外引入？**

检查：所有 Requirement 均围绕"升级依赖版本"和"适配配置"，无新功能引入，无架构变更，无 PHP 端变更。符合 Non-scope 约束。

**Q4: 验证标准是否覆盖完整？**

- `npm run test` green → Req 1 AC4、Req 2 AC5、Req 5 AC10
- `npm run build` / `npm run release` 成功 → Req 1 AC5/6、Req 2 AC6、Req 5 AC11
- `npm run lint` 无新增错误 → Req 3 AC2、Req 4 AC3、Req 5 AC12

所有验证标准均已覆盖。

**Q5: 依赖列表是否与 PRP-002 一致？**

对照 PRP-002 Scope 表：
- 大版本升级：vite ^7→^8 ✓、vitest ^3→^4 ✓、@vitest/coverage-v8 ^3→^4 ✓、eslint ^9→^10 ✓
- 小版本更新：@vitejs/plugin-vue ^6 ✓、eslint-config-prettier ^10 ✓、eslint-plugin-vue ^10 ✓、@vue/test-utils ^2.4 ✓、prettier ^3.6→^3.8 ✓、jsdom ^29 ✓、fast-check ^4 ✓、sass ^1.92 ✓、autoprefixer ^10 ✓

无遗漏。

### Round 2 — 边界与风险审查

**Q6: 配置适配的 AC 是否过于模糊？**

Req 2 和 Req 4 的 AC 使用了 "WHEN ... 引入了 breaking changes" 的条件句式。这是合理的——在 requirements 阶段无法预知具体的 breaking changes 内容（需要在 design 阶段查阅各工具的 migration guide 确定）。AC 的验证标准（test/build/lint 通过）是明确可测的。

**Q7: Req 6 AC3 的 `npm audit` 要求是否合理？**

要求"零 critical/high 漏洞"是合理的安全底线。如果上游依赖本身存在已知漏洞且无修复版本，这属于 design 阶段需要评估的风险，不影响 requirement 的正确性。

**Q8: Req 7 是否覆盖了所有需要更新的 state 文档？**

当前 `docs/state/architecture.md` 的技术选型表中明确列出了 Vite ^7、Vitest ^3、ESLint ^9。Req 7 AC1/2/3 覆盖了这三项。AC4/5 覆盖了 Rolldown 相关描述更新。如果 `docs/state/` 中还有其他文件涉及版本描述，design 阶段应扫描确认。

**结论**: 所有决策已体现，无遗漏，无违规。配置适配的具体内容留给 design 阶段确定。

### Round 3 — 补充维度

**Q9: 各 requirement 之间是否存在矛盾或重叠？**

- Req 1 AC4 和 Req 2 AC5 都要求 `npm run test` 通过——Req 1 验证的是升级后的基本通过，Req 2 验证的是配置适配后的通过。两者是顺序关系而非重叠：先升级版本号（Req 1），再适配配置（Req 2）。同理 Req 3/4 的关系。
- Req 5 AC10/11/12 与前面的验证 AC 看似重复，但 Req 5 验证的是"所有小版本更新完成后"的整体状态，是最终集成验证。无矛盾。

**Q10: 是否混入了实现细节？**

- Req 5 列出了具体包名和版本约束——这是 toolchain upgrade spec 的特殊性，包名本身就是需求的"what"而非"how"。PRP-002 已明确列出这些包，requirements 引用它们是合理的。
- Req 7 指定了具体文件路径 `docs/state/architecture.md`——这是 SSOT 文档的位置，属于"更新什么"而非"怎么更新"。
- 无其他实现细节混入。


---

## Gatekeep Log

**校验时间**: 2025-07-15
**校验结果**: ⚠️ 已修正后通过

### 修正项

- [结构] `## Socratic Review Log` 标题修正为 `## Socratic Review`（符合标准 section 命名）
- [术语] 移除 Glossary 中孤立术语 `Build_Chain`（未在任何 AC 中作为 Subject 使用）
- [语体] Req 6 AC1/AC2 中的 `package-lock.json` / `lock file` 统一改为 Glossary 术语 `Lock_File`
- [内容] Socratic Review 补充 Round 3：覆盖"requirement 间矛盾/重叠"和"是否混入实现细节"两个维度

### 合规检查

- [x] 无 TBD / TODO / 待定 / 占位符
- [x] 无空 section 或不完整的列表
- [x] 内部引用一致
- [x] 无 markdown 格式错误
- [x] 一级标题存在且正确
- [x] Introduction 存在，描述了 feature 范围
- [x] Introduction 明确了不涉及的内容（Non-scope）
- [x] Glossary 存在且非空
- [x] Requirements section 存在且包含 7 条 requirement
- [x] 各 section 之间使用 `---` 分隔
- [x] Glossary 术语在 AC 中被实际使用
- [x] AC 中使用的领域概念在 Glossary 中有定义
- [x] 所有 AC 使用 EARS 模式（THE/WHEN/IF 开头）
- [x] AC Subject 使用 Glossary 术语
- [x] AC 编号连续无跳号
- [x] User Story 使用中文行文
- [x] 无实现细节混入（包名作为需求本体可接受）
- [x] Goal CR 所有决策已体现
- [x] Non-goal / Scope 边界清晰
- [x] 完成标准充分
- [x] 可 design 性充分

### Clarification Round

**状态**: 已回答

**Q1:** Req 2 和 Req 4 的 AC 使用了条件句式"WHEN ... 引入了 breaking changes"。在 design 阶段，如果查阅 migration guide 后发现某工具实际上没有 breaking changes（即条件不成立），该 requirement 应如何处理？

- A) 条件不成立时该 AC 自动视为通过（WHEN 语义：条件不触发则不适用）
- B) 无论是否有 breaking changes，都应审查配置并记录"无需变更"的结论
- C) 如果确认无 breaking changes，在 design 阶段将该 requirement 标记为 N/A 并说明原因
- D) 其他（请说明）

**A:** A — 条件不成立时该 AC 自动视为通过

**Q2:** Req 6 AC3 要求"零 critical/high 漏洞"。如果上游依赖存在已知漏洞且当前无修复版本（即升级前就已存在的漏洞），是否算作本次升级引入的漏洞？

- A) 仅计算本次升级新引入的漏洞（升级前已存在的不计入）
- B) 无论新旧，最终状态必须零 critical/high（如无法满足则记录为已知风险并继续）
- C) 升级前先记录 baseline，升级后对比 diff，仅 diff 中新增的算违规
- D) 其他（请说明）

**A:** B — 无论新旧，最终状态必须零 critical/high（如无法满足则记录为已知风险并继续）

**Q3:** Req 5 列出了 9 个小版本依赖的目标版本。如果在实际升级时发现某个包的最新小版本与 Vite 8 / ESLint 10 存在兼容性问题，应如何处理？

- A) 回退到兼容的最高小版本，在 design 文档中记录原因
- B) 仍升级到最新小版本，将兼容性问题作为 bug 修复（可能需要配置调整）
- C) 将该包从本次升级范围中排除，记录为后续跟进项
- D) 其他（请说明）

**A:** B — 仍升级到最新小版本，将兼容性问题作为 bug 修复
