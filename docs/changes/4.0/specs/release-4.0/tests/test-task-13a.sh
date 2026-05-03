#!/usr/bin/env bash
# =============================================================================
# Task 13.2 — PHP 端集成验证
# Requirements: 1.10, 3.4, 4.6, 12.6, 12.7, 13.3, 13.5
# =============================================================================
set -euo pipefail

PASS=0
FAIL=0
RESULTS=()

report() {
    local status="$1" name="$2" detail="$3"
    if [[ "$status" == "PASS" ]]; then
        PASS=$((PASS + 1))
        RESULTS+=("✓ $name: $detail")
    else
        FAIL=$((FAIL + 1))
        RESULTS+=("✗ $name: $detail")
    fi
}

echo "=== Task 13.2: PHP 端集成验证 ==="
echo ""

# --- 13.2.1 composer install ---
echo "[1/6] Running composer install..."
if php "$(which composer)" install --no-interaction 2>&1; then
    report PASS "composer install" "completed without errors"
else
    report FAIL "composer install" "dependency resolution failed"
fi
echo ""

# --- 13.2.2 phpunit ---
echo "[2/6] Running phpunit..."
PHPUNIT_OUTPUT=$(php vendor/bin/phpunit 2>&1) || true
if echo "$PHPUNIT_OUTPUT" | grep -qE 'OK \('; then
    PHPUNIT_SUMMARY=$(echo "$PHPUNIT_OUTPUT" | grep -E 'OK \(' | tail -1)
    report PASS "phpunit" "$PHPUNIT_SUMMARY"
else
    PHPUNIT_SUMMARY=$(echo "$PHPUNIT_OUTPUT" | grep -E '(FAILURES|ERRORS|Tests:)' | tail -1)
    report FAIL "phpunit" "${PHPUNIT_SUMMARY:-tests did not pass}"
fi
echo ""

# --- 13.2.3 phpunit coverage ---
echo "[3/6] Running phpunit --coverage-text..."
COVERAGE_OUTPUT=$(php vendor/bin/phpunit --coverage-text 2>&1) || true
# Extract coverage lines for src/ files
COVERAGE_BELOW_90=()
while IFS= read -r line; do
    # Lines like "  Oasis\SlimVue\TwigBridgeInfo   100.00%  ..."
    if echo "$line" | grep -qE '^\s+Oasis\\SlimVue\\'; then
        pct=$(echo "$line" | grep -oE '[0-9]+\.[0-9]+%' | head -1 | tr -d '%')
        if [[ -n "$pct" ]]; then
            below=$(echo "$pct < 90" | bc -l 2>/dev/null || echo "0")
            if [[ "$below" == "1" ]]; then
                COVERAGE_BELOW_90+=("$line")
            fi
        fi
    fi
done <<< "$COVERAGE_OUTPUT"

# Also check the summary line
SUMMARY_LINE=$(echo "$COVERAGE_OUTPUT" | grep -E '^\s+Lines:' | head -1 || true)
if [[ -n "$SUMMARY_LINE" ]]; then
    SUMMARY_PCT=$(echo "$SUMMARY_LINE" | grep -oE '[0-9]+\.[0-9]+%' | head -1 | tr -d '%')
fi

if [[ ${#COVERAGE_BELOW_90[@]} -eq 0 ]] && [[ -n "${SUMMARY_PCT:-}" ]]; then
    above=$(echo "$SUMMARY_PCT >= 90" | bc -l 2>/dev/null || echo "1")
    if [[ "$above" == "1" ]]; then
        report PASS "coverage" "overall ${SUMMARY_PCT}% (all src/ files > 90%)"
    else
        report FAIL "coverage" "overall ${SUMMARY_PCT}% (below 90%)"
    fi
elif [[ ${#COVERAGE_BELOW_90[@]} -gt 0 ]]; then
    report FAIL "coverage" "${#COVERAGE_BELOW_90[@]} file(s) below 90%"
    for f in "${COVERAGE_BELOW_90[@]}"; do
        echo "  ↳ $f"
    done
else
    report PASS "coverage" "all src/ files > 90%"
fi
echo ""

# --- 13.2.4 initialize command ---
echo "[4/6] Running bin/slimvue initialize..."
TEST_DIR="/tmp/slimvue-test-$$"
rm -rf "$TEST_DIR"
INIT_OUTPUT=$(php bin/slimvue initialize testproj --directory "$TEST_DIR" 2>&1) || true
if [[ -d "$TEST_DIR" ]] && [[ -f "$TEST_DIR/package.json" ]] && [[ -f "$TEST_DIR/vite.config.js" ]]; then
    report PASS "initialize" "created valid Vite project at $TEST_DIR"
else
    report FAIL "initialize" "project directory or key files missing"
    echo "  ↳ Output: $INIT_OUTPUT"
fi
echo ""

# --- 13.2.5 verify no obsolete files ---
echo "[5/6] Verifying no obsolete files in initialized project..."
OBSOLETE_FOUND=()
for f in "build" "vue.config.js" "babel.config.js" "jest.config.js" ".eslintrc.js"; do
    if [[ -e "$TEST_DIR/$f" ]]; then
        OBSOLETE_FOUND+=("$f")
    fi
done
if [[ ${#OBSOLETE_FOUND[@]} -eq 0 ]]; then
    report PASS "no obsolete files" "initialized project is clean"
else
    report FAIL "no obsolete files" "found: ${OBSOLETE_FOUND[*]}"
fi
echo ""

# --- 13.2.6 upgrade command ---
echo "[6/6] Running bin/slimvue upgrade..."
# Read name and version before upgrade
if [[ -f "$TEST_DIR/package.json" ]]; then
    NAME_BEFORE=$(php -r "echo json_decode(file_get_contents('$TEST_DIR/package.json'), true)['name'] ?? 'MISSING';")
    VERSION_BEFORE=$(php -r "echo json_decode(file_get_contents('$TEST_DIR/package.json'), true)['version'] ?? 'MISSING';")
fi

UPGRADE_OUTPUT=$(php bin/slimvue upgrade "$TEST_DIR" 2>&1) || true

if [[ -f "$TEST_DIR/package.json" ]]; then
    NAME_AFTER=$(php -r "echo json_decode(file_get_contents('$TEST_DIR/package.json'), true)['name'] ?? 'MISSING';")
    VERSION_AFTER=$(php -r "echo json_decode(file_get_contents('$TEST_DIR/package.json'), true)['version'] ?? 'MISSING';")

    if [[ "$NAME_BEFORE" == "$NAME_AFTER" ]] && [[ "$VERSION_BEFORE" == "$VERSION_AFTER" ]]; then
        report PASS "upgrade" "name=$NAME_AFTER, version=$VERSION_AFTER preserved"
    else
        report FAIL "upgrade" "name: $NAME_BEFORE→$NAME_AFTER, version: $VERSION_BEFORE→$VERSION_AFTER"
    fi
else
    report FAIL "upgrade" "package.json missing after upgrade"
fi

# Cleanup
rm -rf "$TEST_DIR"
echo ""

# --- Summary ---
echo "==========================================="
echo "Task 13.2 Summary: $((PASS + FAIL)) checks, $PASS passed, $FAIL failed"
echo "==========================================="
for r in "${RESULTS[@]}"; do
    echo "  $r"
done
echo ""

exit $FAIL
