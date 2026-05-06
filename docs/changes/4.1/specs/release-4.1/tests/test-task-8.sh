#!/usr/bin/env bash
# =============================================================================
# Release 4.1 — Task 8: 手工测试脚本
# 覆盖 8.2 前端构建链集成验证 / 8.3 PHP 端集成验证 / 8.4 版本声明验证
# =============================================================================

set -euo pipefail

PASS=0
FAIL=0
RESULTS=()

report() {
    local status="$1" task="$2" detail="${3:-}"
    if [[ "$status" == "PASS" ]]; then
        ((PASS++))
        RESULTS+=("✅ PASS: $task")
    else
        ((FAIL++))
        RESULTS+=("❌ FAIL: $task — $detail")
    fi
}

# Resolve project root (script is at .kiro/specs/release-4.1/tests/)
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/../../../.." && pwd)"

echo "=========================================="
echo " Release 4.1 — Manual Test Suite"
echo "=========================================="
echo "Project root: $PROJECT_ROOT"
echo ""

# =============================================================================
# 8.2 前端构建链集成验证
# =============================================================================
echo "--- 8.2 前端构建链集成验证 ---"
FRONTEND_DIR="$PROJECT_ROOT/slimvue-template"

# 8.2.1 npm install
echo "[8.2.1] npm install..."
if (cd "$FRONTEND_DIR" && npm install --loglevel=error 2>&1); then
    report "PASS" "8.2.1 npm install"
else
    report "FAIL" "8.2.1 npm install" "npm install failed"
fi
echo ""

# 8.2.2 npm run test
echo "[8.2.2] npm run test..."
if (cd "$FRONTEND_DIR" && npm run test 2>&1); then
    report "PASS" "8.2.2 npm run test"
else
    report "FAIL" "8.2.2 npm run test" "tests failed"
fi
echo ""

# 8.2.3 npm run build
echo "[8.2.3] npm run build..."
if (cd "$FRONTEND_DIR" && npm run build 2>&1); then
    if [[ -d "$FRONTEND_DIR/dist" ]]; then
        report "PASS" "8.2.3 npm run build (dist/ exists)"
    else
        report "FAIL" "8.2.3 npm run build" "dist/ directory not found"
    fi
else
    report "FAIL" "8.2.3 npm run build" "build command failed"
fi
echo ""

# 8.2.4 npm run release
echo "[8.2.4] npm run release..."
# Clean dist first to verify release produces fresh output
rm -rf "$FRONTEND_DIR/dist"
if (cd "$FRONTEND_DIR" && npm run release 2>&1); then
    if [[ -d "$FRONTEND_DIR/dist" ]]; then
        report "PASS" "8.2.4 npm run release (dist/ exists)"
    else
        report "FAIL" "8.2.4 npm run release" "dist/ directory not found"
    fi
else
    report "FAIL" "8.2.4 npm run release" "release command failed"
fi
echo ""

# 8.2.5 npm run lint
echo "[8.2.5] npm run lint..."
if (cd "$FRONTEND_DIR" && npm run lint 2>&1); then
    report "PASS" "8.2.5 npm run lint"
else
    report "FAIL" "8.2.5 npm run lint" "lint errors found"
fi
echo ""

# 8.2.6 npm audit
echo "[8.2.6] npm audit..."
AUDIT_OUTPUT=$(cd "$FRONTEND_DIR" && npm audit 2>&1) || true
# Check for critical/high vulnerabilities
if echo "$AUDIT_OUTPUT" | grep -qiE '([0-9]+ critical|[0-9]+ high)'; then
    # Further check: are there actually >0 critical or high?
    CRITICAL=$(echo "$AUDIT_OUTPUT" | grep -oiE '[0-9]+ critical' | grep -oE '[0-9]+' | head -1)
    HIGH=$(echo "$AUDIT_OUTPUT" | grep -oiE '[0-9]+ high' | grep -oE '[0-9]+' | head -1)
    CRITICAL=${CRITICAL:-0}
    HIGH=${HIGH:-0}
    if [[ "$CRITICAL" -gt 0 || "$HIGH" -gt 0 ]]; then
        report "FAIL" "8.2.6 npm audit" "critical=$CRITICAL high=$HIGH"
    else
        report "PASS" "8.2.6 npm audit (0 critical/high)"
    fi
else
    report "PASS" "8.2.6 npm audit (0 critical/high)"
fi
echo ""

# =============================================================================
# 8.3 PHP 端集成验证
# =============================================================================
echo "--- 8.3 PHP 端集成验证 ---"

# 8.3.1 composer install
echo "[8.3.1] composer install..."
COMPOSER_BIN=$(which composer 2>/dev/null || echo "composer")
if (cd "$PROJECT_ROOT" && php "$COMPOSER_BIN" install --no-interaction 2>&1); then
    report "PASS" "8.3.1 composer install"
else
    report "FAIL" "8.3.1 composer install" "composer install failed"
fi
echo ""

# 8.3.2 phpunit
echo "[8.3.2] phpunit..."
if (cd "$PROJECT_ROOT" && vendor/bin/phpunit 2>&1); then
    report "PASS" "8.3.2 phpunit"
else
    report "FAIL" "8.3.2 phpunit" "phpunit tests failed"
fi
echo ""

# 8.3.3 phpstan
echo "[8.3.3] phpstan analyse..."
if (cd "$PROJECT_ROOT" && vendor/bin/phpstan analyse 2>&1); then
    report "PASS" "8.3.3 phpstan analyse"
else
    report "FAIL" "8.3.3 phpstan analyse" "static analysis failed"
fi
echo ""

# =============================================================================
# 8.4 版本声明验证
# =============================================================================
echo "--- 8.4 版本声明验证 ---"

# 8.4.1 package.json version checks
echo "[8.4.1] package.json version declarations..."
PKG_FILE="$FRONTEND_DIR/package.json"
VERSION_ERRORS=""

check_pkg_version() {
    local pkg="$1" expected_pattern="$2"
    local actual
    actual=$(node -e "const p=JSON.parse(require('fs').readFileSync('$PKG_FILE','utf8')); console.log(p.devDependencies['$pkg'] || 'NOT_FOUND')")
    if [[ "$actual" == "NOT_FOUND" ]]; then
        VERSION_ERRORS+="  $pkg: NOT FOUND (expected $expected_pattern)\n"
    elif ! echo "$actual" | grep -qE "$expected_pattern"; then
        VERSION_ERRORS+="  $pkg: $actual (expected $expected_pattern)\n"
    fi
}

check_pkg_version "vite" '^\^8'
check_pkg_version "vitest" '^\^4'
check_pkg_version "@vitest/coverage-v8" '^\^4'
check_pkg_version "eslint" '^\^10'
check_pkg_version "@vitejs/plugin-vue" '^\^6'
check_pkg_version "eslint-config-prettier" '^\^10'
check_pkg_version "eslint-plugin-vue" '^\^10'
check_pkg_version "@vue/test-utils" '^\^2'
check_pkg_version "prettier" '^\^3'
check_pkg_version "jsdom" '^\^29'
check_pkg_version "fast-check" '^\^4'
check_pkg_version "sass" '^\^1'
check_pkg_version "autoprefixer" '^\^10'

if [[ -z "$VERSION_ERRORS" ]]; then
    report "PASS" "8.4.1 package.json version declarations"
else
    report "FAIL" "8.4.1 package.json version declarations" "$(echo -e "$VERSION_ERRORS")"
fi
echo ""

# 8.4.2 architecture.md version checks
echo "[8.4.2] architecture.md version checks..."
ARCH_FILE="$PROJECT_ROOT/docs/state/architecture.md"
ARCH_ERRORS=""

if ! grep -q 'Vite（Rolldown）' "$ARCH_FILE"; then
    ARCH_ERRORS+="  Missing 'Vite（Rolldown）' in architecture.md\n"
fi
if ! grep -qE '\^8' "$ARCH_FILE"; then
    ARCH_ERRORS+="  Missing '^8' (Vite version) in architecture.md\n"
fi
if ! grep -qE 'Vitest.*\^4' "$ARCH_FILE"; then
    ARCH_ERRORS+="  Missing 'Vitest ^4' in architecture.md\n"
fi
if ! grep -qE 'ESLint.*\^10' "$ARCH_FILE"; then
    ARCH_ERRORS+="  Missing 'ESLint ^10' in architecture.md\n"
fi

if [[ -z "$ARCH_ERRORS" ]]; then
    report "PASS" "8.4.2 architecture.md version declarations"
else
    report "FAIL" "8.4.2 architecture.md version declarations" "$(echo -e "$ARCH_ERRORS")"
fi
echo ""

# =============================================================================
# Summary
# =============================================================================
echo "=========================================="
echo " SUMMARY"
echo "=========================================="
for r in "${RESULTS[@]}"; do
    echo "$r"
done
echo ""
echo "Total: $((PASS + FAIL)) | PASS: $PASS | FAIL: $FAIL"
echo "=========================================="

if [[ "$FAIL" -gt 0 ]]; then
    exit 1
else
    echo "All tests passed! ✅"
    exit 0
fi
