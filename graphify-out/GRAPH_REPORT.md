# Graph Report - .  (2026-05-03)

## Corpus Check
- Corpus is ~5,073 words - fits in a single context window. You may not need a graph.

## Summary
- 97 nodes · 84 edges · 24 communities detected
- Extraction: 80% EXTRACTED · 20% INFERRED · 0% AMBIGUOUS · INFERRED: 17 edges (avg confidence: 0.8)
- Token cost: 0 input · 0 output

## Community Hubs (Navigation)
- [[_COMMUNITY_Core Architecture & SSOT|Core Architecture & SSOT]]
- [[_COMMUNITY_SlimVue.js Frontend Module|SlimVue.js Frontend Module]]
- [[_COMMUNITY_Doc Lifecycle & Issues|Doc Lifecycle & Issues]]
- [[_COMMUNITY_Project Overview|Project Overview]]
- [[_COMMUNITY_Bridge & Manual|Bridge & Manual]]
- [[_COMMUNITY_Frontend Build & Template|Frontend Build & Template]]
- [[_COMMUNITY_TwigBridgeInfo Implementation|TwigBridgeInfo Implementation]]
- [[_COMMUNITY_MyClock Component|MyClock Component]]
- [[_COMMUNITY_Upgrade Command|Upgrade Command]]
- [[_COMMUNITY_Initialize Command|Initialize Command]]
- [[_COMMUNITY_Bridge Interface|Bridge Interface]]
- [[_COMMUNITY_Vue CLI Config|Vue CLI Config]]
- [[_COMMUNITY_Build Environment|Build Environment]]
- [[_COMMUNITY_Index Entry|Index Entry]]
- [[_COMMUNITY_Test Entry|Test Entry]]
- [[_COMMUNITY_Prettier Config|Prettier Config]]
- [[_COMMUNITY_Babel Config|Babel Config]]
- [[_COMMUNITY_PostCSS Config|PostCSS Config]]
- [[_COMMUNITY_Publish Script|Publish Script]]
- [[_COMMUNITY_Main Page Entry|Main Page Entry]]
- [[_COMMUNITY_Subpage Entry|Subpage Entry]]
- [[_COMMUNITY_App Component|App Component]]
- [[_COMMUNITY_SubPage Component|SubPage Component]]
- [[_COMMUNITY_HelloWorld Component|HelloWorld Component]]

## God Nodes (most connected - your core abstractions)
1. `SlimVue Project` - 7 edges
2. `TwigBridgeInfo` - 6 edges
3. `Frontend Layer (Vue 2 MPA)` - 5 edges
4. `Bridge Mechanism (PHP→JS)` - 5 edges
5. `SlimVueUpgradeCommand` - 4 edges
6. `SlimVueInitializeCommand` - 4 edges
7. `SlimVue System Architecture` - 4 edges
8. `Getting Started Guide` - 4 edges
9. `Changes Directory` - 4 edges
10. `getFullDateTime()` - 3 edges

## Surprising Connections (you probably didn't know these)
- `SlimVue Logo` --conceptually_related_to--> `SlimVue Project`  [INFERRED]
  slimvue-template/src/assets/logo.png → PROJECT.md
- `Bridge Mechanism (PHP→JS)` --conceptually_related_to--> `Twig Bridge Mechanism`  [INFERRED]
  docs/state/architecture.md → PROJECT.md
- `SlimVue Template README` --conceptually_related_to--> `Frontend Layer (Vue 2 MPA)`  [INFERRED]
  slimvue-template/README.md → docs/state/architecture.md
- `SlimVue README` --references--> `SlimVue Project`  [EXTRACTED]
  README.md → PROJECT.md
- `CLI Layer (Symfony Console)` --conceptually_related_to--> `CLI Tool (slimvue)`  [INFERRED]
  docs/state/architecture.md → PROJECT.md

## Hyperedges (group relationships)
- **Three-Layer Architecture** — arch_php_layer, arch_cli_layer, arch_frontend_layer [EXTRACTED 1.00]
- **Bridge Data Flow** — dm_bridge_interface, dm_twig_bridge_info, dm_slimvue_js [EXTRACTED 1.00]
- **Documentation Hierarchy** — state_ssot, manual_readme, proposals_readme, notes_readme, changes_readme, issues_readme [EXTRACTED 1.00]

## Communities

### Community 0 - "Core Architecture & SSOT"
Cohesion: 0.2
Nodes (11): Documentation Hierarchy, Agent Instructions, SSOT Principle, CLI Layer (Symfony Console), PHP Application Layer, SlimVue System Architecture, Initialize Command Usage, Manual Directory (+3 more)

### Community 1 - "SlimVue.js Frontend Module"
Cohesion: 0.22
Nodes (2): log(), mount()

### Community 2 - "Doc Lifecycle & Issues"
Cohesion: 0.2
Nodes (10): Changelog System, Changes Directory, Spec Archive Process, L-Series Issues (Production Bugs), Issue Management, Release Issues (Stabilize Phase), Issue Severity Levels, Proposal Lifecycle (+2 more)

### Community 3 - "Project Overview"
Cohesion: 0.25
Nodes (8): SlimVue Logo, Multi-Page Entry System, PHP Composer Library, SlimVue Project, Twig Bridge Mechanism, Vue Frontend Template, SlimApp (External), SlimVue README

### Community 4 - "Bridge & Manual"
Cohesion: 0.29
Nodes (8): Bridge Mechanism (PHP→JS), Twig Rendering, SlimVueBridgeInterface (Data Model), TwigBridgeInfo Implementation (Data Model), Add New Page Guide, Composer Install Step, Getting Started Guide, PHP Integration Guide

### Community 5 - "Frontend Build & Template"
Cohesion: 0.25
Nodes (8): Frontend Layer (Vue 2 MPA), Webpack Build (Vue CLI 4), Log Level Constants, slimvue.js Core Module (Data Model), Vue Mount Point (#app), HTML Page Template, SlimVue Template README, HtmlWebpackPlugin Integration

### Community 6 - "TwigBridgeInfo Implementation"
Cohesion: 0.33
Nodes (1): TwigBridgeInfo

### Community 7 - "MyClock Component"
Cohesion: 0.47
Nodes (3): fullDateTime(), getFullDateTime(), prefixDateNum()

### Community 8 - "Upgrade Command"
Cohesion: 0.4
Nodes (1): SlimVueUpgradeCommand

### Community 9 - "Initialize Command"
Cohesion: 0.4
Nodes (1): SlimVueInitializeCommand

### Community 10 - "Bridge Interface"
Cohesion: 0.5
Nodes (0): 

### Community 11 - "Vue CLI Config"
Cohesion: 1.0
Nodes (0): 

### Community 12 - "Build Environment"
Cohesion: 1.0
Nodes (2): Build Configuration (Data Model), Environment Variables

### Community 13 - "Index Entry"
Cohesion: 1.0
Nodes (0): 

### Community 14 - "Test Entry"
Cohesion: 1.0
Nodes (0): 

### Community 15 - "Prettier Config"
Cohesion: 1.0
Nodes (0): 

### Community 16 - "Babel Config"
Cohesion: 1.0
Nodes (0): 

### Community 17 - "PostCSS Config"
Cohesion: 1.0
Nodes (0): 

### Community 18 - "Publish Script"
Cohesion: 1.0
Nodes (0): 

### Community 19 - "Main Page Entry"
Cohesion: 1.0
Nodes (0): 

### Community 20 - "Subpage Entry"
Cohesion: 1.0
Nodes (0): 

### Community 21 - "App Component"
Cohesion: 1.0
Nodes (0): 

### Community 22 - "SubPage Component"
Cohesion: 1.0
Nodes (0): 

### Community 23 - "HelloWorld Component"
Cohesion: 1.0
Nodes (0): 

## Knowledge Gaps
- **21 isolated node(s):** `PHP Composer Library`, `Vue Frontend Template`, `Multi-Page Entry System`, `SlimApp (External)`, `PHP Application Layer` (+16 more)
  These have ≤1 connection - possible missing edges or undocumented components.
- **Thin community `Vue CLI Config`** (2 nodes): `vue.config.js`, `chainWebpack()`
  Too small to be a meaningful cluster - may be noise or needs more connections extracted.
- **Thin community `Build Environment`** (2 nodes): `Build Configuration (Data Model)`, `Environment Variables`
  Too small to be a meaningful cluster - may be noise or needs more connections extracted.
- **Thin community `Index Entry`** (1 nodes): `index.php`
  Too small to be a meaningful cluster - may be noise or needs more connections extracted.
- **Thin community `Test Entry`** (1 nodes): `test.php`
  Too small to be a meaningful cluster - may be noise or needs more connections extracted.
- **Thin community `Prettier Config`** (1 nodes): `prettier.config.js`
  Too small to be a meaningful cluster - may be noise or needs more connections extracted.
- **Thin community `Babel Config`** (1 nodes): `babel.config.js`
  Too small to be a meaningful cluster - may be noise or needs more connections extracted.
- **Thin community `PostCSS Config`** (1 nodes): `postcss.config.js`
  Too small to be a meaningful cluster - may be noise or needs more connections extracted.
- **Thin community `Publish Script`** (1 nodes): `publish.js`
  Too small to be a meaningful cluster - may be noise or needs more connections extracted.
- **Thin community `Main Page Entry`** (1 nodes): `index.js`
  Too small to be a meaningful cluster - may be noise or needs more connections extracted.
- **Thin community `Subpage Entry`** (1 nodes): `index.js`
  Too small to be a meaningful cluster - may be noise or needs more connections extracted.
- **Thin community `App Component`** (1 nodes): `App.vue`
  Too small to be a meaningful cluster - may be noise or needs more connections extracted.
- **Thin community `SubPage Component`** (1 nodes): `SubPage.vue`
  Too small to be a meaningful cluster - may be noise or needs more connections extracted.
- **Thin community `HelloWorld Component`** (1 nodes): `HelloWorld.vue`
  Too small to be a meaningful cluster - may be noise or needs more connections extracted.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `Frontend Layer (Vue 2 MPA)` connect `Frontend Build & Template` to `Core Architecture & SSOT`, `Bridge & Manual`?**
  _High betweenness centrality (0.055) - this node is a cross-community bridge._
- **Why does `SlimVue System Architecture` connect `Core Architecture & SSOT` to `Frontend Build & Template`?**
  _High betweenness centrality (0.049) - this node is a cross-community bridge._
- **Why does `Bridge Mechanism (PHP→JS)` connect `Bridge & Manual` to `Project Overview`, `Frontend Build & Template`?**
  _High betweenness centrality (0.047) - this node is a cross-community bridge._
- **Are the 2 inferred relationships involving `Frontend Layer (Vue 2 MPA)` (e.g. with `slimvue.js Core Module (Data Model)` and `SlimVue Template README`) actually correct?**
  _`Frontend Layer (Vue 2 MPA)` has 2 INFERRED edges - model-reasoned connections that need verification._
- **Are the 3 inferred relationships involving `Bridge Mechanism (PHP→JS)` (e.g. with `Twig Bridge Mechanism` and `SlimVueBridgeInterface (Data Model)`) actually correct?**
  _`Bridge Mechanism (PHP→JS)` has 3 INFERRED edges - model-reasoned connections that need verification._
- **What connects `PHP Composer Library`, `Vue Frontend Template`, `Multi-Page Entry System` to the rest of the system?**
  _21 weakly-connected nodes found - possible documentation gaps or missing edges._