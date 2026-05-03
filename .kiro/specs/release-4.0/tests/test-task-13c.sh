#!/usr/bin/env bash
# =============================================================================
# Task 13.4 — 迁移工具验证
# Requirements: 15.7, 16.8
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

echo "=== Task 13.4: 迁移工具验证 ==="
echo ""

# --- Create mock v3 project ---
MOCK_DIR="/tmp/slimvue-v3-mock-$$"
rm -rf "$MOCK_DIR"
mkdir -p "$MOCK_DIR"

echo "[1/4] Creating mock v3 project at $MOCK_DIR..."

# composer.json with old PHP version and deprecated deps
cat > "$MOCK_DIR/composer.json" << 'COMPOSER_EOF'
{
    "require": {
        "php": ">=7.0",
        "silex/silex": "^2.2"
    },
    "require-dev": {
        "phpunit/phpunit": "^9.0"
    }
}
COMPOSER_EOF

# package.json with Vue 2 and Vue CLI scripts
cat > "$MOCK_DIR/package.json" << 'PKG_EOF'
{
    "name": "mock-v3-project",
    "version": "3.0.0",
    "dependencies": {
        "vue": "^2.6.11",
        "core-js": "^3.6.5"
    },
    "devDependencies": {
        "@vue/cli-service": "~4.5.0",
        "jest": "^26.6.3",
        "vue-template-compiler": "^2.6.11"
    },
    "scripts": {
        "serve": "vue-cli-service serve",
        "build": "vue-cli-service build",
        "test": "jest --no-cache",
        "lint": "vue-cli-service lint"
    }
}
PKG_EOF

# Obsolete config files
cat > "$MOCK_DIR/vue.config.js" << 'EOF'
module.exports = { publicPath: '/' };
EOF

cat > "$MOCK_DIR/babel.config.js" << 'EOF'
module.exports = { presets: ['@vue/cli-plugin-babel/preset'] };
EOF

cat > "$MOCK_DIR/jest.config.js" << 'EOF'
module.exports = { preset: '@vue/cli-plugin-unit-jest' };
EOF

cat > "$MOCK_DIR/.eslintrc.js" << 'EOF'
module.exports = { extends: ['plugin:vue/essential'] };
EOF

mkdir -p "$MOCK_DIR/build"
echo "// old webpack config" > "$MOCK_DIR/build/webpack.config.js"

# JS file with Vue 2 API patterns
mkdir -p "$MOCK_DIR/src"
cat > "$MOCK_DIR/src/main.js" << 'JSEOF'
import Vue from 'vue';
import App from './App.vue';

Vue.config.productionTip = false;
Vue.prototype.$myHelper = function() { return 'hello'; };

new Vue({
    render: h => h(App),
}).$mount('#app');
JSEOF

# PHP file with deprecated API
mkdir -p "$MOCK_DIR/src-php"
cat > "$MOCK_DIR/src-php/app.php" << 'PHPEOF'
<?php
use Silex\Application;
$app = new Application();
$app->get('/', function() { return 'Hello'; });
$app->run();
PHPEOF

report PASS "create mock v3 project" "mock project created with all v3 artifacts"
echo ""

# --- 13.4.2 Run migrate-check (should detect issues) ---
echo "[2/4] Running bin/slimvue-migrate-check on mock v3 project..."
CHECK_EXIT_BEFORE=0
CHECK_OUTPUT_BEFORE=$(php bin/slimvue-migrate-check "$MOCK_DIR" 2>&1) || CHECK_EXIT_BEFORE=$?

# Should fail (exit 1) because v3 project has issues
if [[ $CHECK_EXIT_BEFORE -ne 0 ]]; then
    report PASS "migrate-check (pre)" "correctly detected issues (exit code $CHECK_EXIT_BEFORE)"
else
    report FAIL "migrate-check (pre)" "should have detected issues but reported all pass"
fi
echo "  Output:"
echo "$CHECK_OUTPUT_BEFORE" | sed 's/^/    /'
echo ""

# --- 13.4.3 Run migrate ---
echo "[3/4] Running bin/slimvue-migrate on mock v3 project..."
MIGRATE_OUTPUT=$(php bin/slimvue-migrate "$MOCK_DIR" 2>&1) || true
MIGRATE_EXIT=$?

# Check that migration made changes
CHANGE_COUNT=$(echo "$MIGRATE_OUTPUT" | grep -c '✓' || echo "0")
if [[ $CHANGE_COUNT -gt 0 ]]; then
    report PASS "migrate" "$CHANGE_COUNT change(s) applied"
else
    report FAIL "migrate" "no changes were applied"
fi
echo "  Output:"
echo "$MIGRATE_OUTPUT" | sed 's/^/    /'
echo ""

# --- 13.4.4 Run migrate-check again (post-migration) ---
echo "[4/4] Running bin/slimvue-migrate-check again (post-migration)..."
CHECK_EXIT_AFTER=0
CHECK_OUTPUT_AFTER=$(php bin/slimvue-migrate-check "$MOCK_DIR" 2>&1) || CHECK_EXIT_AFTER=$?

# The migration script automates what it can (PHP version, obsolete files, JS AST transform, scripts)
# but leaves some items for manual attention (deprecated deps, PHP API patterns).
# We verify that the automated items improved — specifically:
# - PHP version constraint should now pass
# - Removed files should now pass
PHP_CHECK_PASS=false
FILES_CHECK_PASS=false
if echo "$CHECK_OUTPUT_AFTER" | grep -q 'PHP version constraint.*satisfies'; then
    PHP_CHECK_PASS=true
fi
if echo "$CHECK_OUTPUT_AFTER" | grep -q 'No obsolete files found'; then
    FILES_CHECK_PASS=true
fi

if $PHP_CHECK_PASS && $FILES_CHECK_PASS; then
    REMAINING=$(echo "$CHECK_OUTPUT_AFTER" | grep -oE '[0-9]+ failed' | grep -oE '[0-9]+' || echo "0")
    report PASS "migrate-check (post)" "automated items fixed; $REMAINING check(s) remain for manual attention"
else
    report FAIL "migrate-check (post)" "automated items not properly fixed"
fi
echo "  Output:"
echo "$CHECK_OUTPUT_AFTER" | sed 's/^/    /'
echo ""

# Cleanup
rm -rf "$MOCK_DIR"

# --- Summary ---
echo "==========================================="
echo "Task 13.4 Summary: $((PASS + FAIL)) checks, $PASS passed, $FAIL failed"
echo "==========================================="
for r in "${RESULTS[@]}"; do
    echo "  $r"
done
echo ""

exit $FAIL
