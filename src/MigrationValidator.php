<?php

namespace Oasis\SlimVue;

class MigrationValidator
{
    /** @var list<array{name: string, status: string, detail: string, hint?: string}> */
    private array $checks = [];

    /**
     * @param list<array{name: string, status: string, detail: string, hint?: string}> $checks
     * @return array{checks: list<array{name: string, status: string, detail: string, hint?: string}>, summary: array{total: int, passed: int, failed: int}}
     */
    public static function buildReport(array $checks): array
    {
        $passed = 0;
        $failed = 0;
        foreach ($checks as $check) {
            if ($check['status'] === 'pass') {
                $passed++;
            } else {
                $failed++;
            }
        }

        return [
            'checks'  => $checks,
            'summary' => [
                'total'  => count($checks),
                'passed' => $passed,
                'failed' => $failed,
            ],
        ];
    }

    /**
     * Run all checks against the given project directory.
     *
     * @return array{checks: list<array{name: string, status: string, detail: string, hint?: string}>, summary: array{total: int, passed: int, failed: int}}
     */
    public function validate(string $projectDir): array
    {
        $this->checks = [];

        $this->checkPhpVersionConstraint($projectDir);
        $this->checkDeprecatedDependencies($projectDir);
        $this->checkDeprecatedPhpPatterns($projectDir);
        $this->checkDeprecatedJsPatterns($projectDir);
        $this->checkRemovedFiles($projectDir);

        return self::buildReport($this->checks);
    }

    /**
     * Check PHP version constraint in composer.json (Req 15.1).
     */
    public function checkPhpVersionConstraint(string $projectDir): void
    {
        $composerPath = $projectDir . '/composer.json';
        if (!file_exists($composerPath)) {
            $this->addCheck(
                name: 'PHP version constraint',
                status: 'fail',
                detail: 'composer.json not found',
                hint: 'Create a composer.json with "require": {"php": ">=8.5"}',
            );
            return;
        }

        $data = json_decode((string) file_get_contents($composerPath), true);
        if (!is_array($data)) {
            $this->addCheck(
                name: 'PHP version constraint',
                status: 'fail',
                detail: 'composer.json is not valid JSON',
                hint: 'Fix the JSON syntax in composer.json',
            );
            return;
        }

        $phpConstraint = $data['require']['php'] ?? null;
        if ($phpConstraint === null) {
            $this->addCheck(
                name: 'PHP version constraint',
                status: 'fail',
                detail: 'No PHP version constraint found in require.php',
                hint: 'Add "php": ">=8.5" to the require section',
            );
            return;
        }

        if ($this->phpConstraintSatisfies85($phpConstraint)) {
            $this->addCheck(
                name: 'PHP version constraint',
                status: 'pass',
                detail: "PHP constraint '$phpConstraint' satisfies >= 8.5",
            );
        } else {
            $this->addCheck(
                name: 'PHP version constraint',
                status: 'fail',
                detail: "PHP constraint '$phpConstraint' does not satisfy >= 8.5",
                hint: 'Update to "php": ">=8.5" in composer.json',
            );
        }
    }

    /**
     * Check for deprecated dependency references (Req 15.2).
     */
    public function checkDeprecatedDependencies(string $projectDir): void
    {
        $composerPath = $projectDir . '/composer.json';
        if (!file_exists($composerPath)) {
            $this->addCheck(
                name: 'Deprecated dependencies',
                status: 'fail',
                detail: 'composer.json not found',
                hint: 'Create a composer.json without deprecated dependencies',
            );
            return;
        }

        $data = json_decode((string) file_get_contents($composerPath), true);
        if (!is_array($data)) {
            $this->addCheck(
                name: 'Deprecated dependencies',
                status: 'fail',
                detail: 'composer.json is not valid JSON',
                hint: 'Fix the JSON syntax in composer.json',
            );
            return;
        }

        $deprecatedComposer = [
            'silex/silex' => 'Replace with oasis/http',
        ];

        $allDeps = array_merge(
            $data['require'] ?? [],
            $data['require-dev'] ?? [],
        );

        $found = [];
        foreach ($deprecatedComposer as $pkg => $remedy) {
            if (array_key_exists($pkg, $allDeps)) {
                $found[] = "$pkg — $remedy";
            }
        }

        $packageJsonPath = $projectDir . '/package.json';
        if (file_exists($packageJsonPath)) {
            $pkgData = json_decode((string) file_get_contents($packageJsonPath), true);
            if (is_array($pkgData)) {
                $deprecatedNpm = [
                    'vue'                      => ['^2', 'Upgrade to vue ^3'],
                    '@vue/cli-service'         => [null, 'Replace with vite'],
                    '@vue/cli-plugin-babel'    => [null, 'Remove (Vite handles transpilation)'],
                    '@vue/cli-plugin-eslint'   => [null, 'Remove (use eslint directly)'],
                    'babel-core'               => [null, 'Remove (not needed with Vite)'],
                    'babel-eslint'             => [null, 'Remove (not needed with Vite)'],
                    'babel-jest'               => [null, 'Remove (use vitest)'],
                    'vue-template-compiler'    => [null, 'Remove (Vue 3 bundles compiler)'],
                    'vue-jest'                 => [null, 'Remove (use vitest)'],
                    'jest'                     => [null, 'Replace with vitest'],
                    '@vue/cli-plugin-unit-jest' => [null, 'Remove (use vitest)'],
                ];

                $allNpmDeps = array_merge(
                    $pkgData['dependencies'] ?? [],
                    $pkgData['devDependencies'] ?? [],
                );

                foreach ($deprecatedNpm as $pkg => [$versionPrefix, $remedy]) {
                    if (!array_key_exists($pkg, $allNpmDeps)) {
                        continue;
                    }
                    if ($versionPrefix !== null) {
                        $ver = $allNpmDeps[$pkg];
                        if (is_string($ver) && str_starts_with($ver, $versionPrefix)) {
                            $found[] = "$pkg ($ver) — $remedy";
                        }
                    } else {
                        $found[] = "$pkg — $remedy";
                    }
                }
            }
        }

        if ($found === []) {
            $this->addCheck(
                name: 'Deprecated dependencies',
                status: 'pass',
                detail: 'No deprecated dependencies found',
            );
        } else {
            $this->addCheck(
                name: 'Deprecated dependencies',
                status: 'fail',
                detail: 'Found deprecated dependencies: ' . implode('; ', $found),
                hint: 'Remove or replace the listed dependencies',
            );
        }
    }

    /**
     * Check for deprecated PHP API patterns (Req 15.3).
     */
    public function checkDeprecatedPhpPatterns(string $projectDir): void
    {
        $patterns = [
            '/\bSilex\\\\Application\b/'                  => 'Silex class reference — replace with oasis/http',
            '/\bSilex\\\\Provider\b/'                     => 'Silex provider reference — replace with oasis/http',
            '/\bnew\s+Application\s*\(\s*\)\s*;/'         => 'Silex Application instantiation — replace with MicroKernel',
            '/\$app\s*->\s*(get|post|put|delete|match)\(/' => 'Silex route registration — use YAML routes with oasis/http',
        ];

        $this->scanFilesForPatterns(
            projectDir: $projectDir,
            checkName: 'Deprecated PHP API patterns',
            extensions: ['php'],
            patterns: $patterns,
        );
    }

    /**
     * Check for deprecated JS/Vue API patterns (Req 15.4).
     */
    public function checkDeprecatedJsPatterns(string $projectDir): void
    {
        $patterns = [
            '/\bnew\s+Vue\s*\(/'          => 'Vue 2 instantiation — replace with createApp()',
            '/\bVue\.prototype\b/'         => 'Vue 2 prototype extension — use app.config.globalProperties',
            '/\bjest\.fn\s*\(/'            => 'Jest API — replace with vi.fn()',
            '/\bjest\.spyOn\s*\(/'         => 'Jest API — replace with vi.spyOn()',
            '/\bjest\.mock\s*\(/'          => 'Jest API — replace with vi.mock()',
            '/\bVue\.config\.productionTip\b/' => 'Vue 2 config — remove (not available in Vue 3)',
        ];

        $this->scanFilesForPatterns(
            projectDir: $projectDir,
            checkName: 'Deprecated JS/Vue API patterns',
            extensions: ['js', 'vue', 'ts', 'jsx', 'tsx'],
            patterns: $patterns,
        );
    }

    /**
     * Check for removed files that should no longer exist (Req 15.5).
     *
     * @param list<string>|null $removedFiles Override the default list of removed files (for testing)
     */
    public function checkRemovedFiles(string $projectDir, ?array $removedFiles = null): void
    {
        $removedFiles ??= [
            'vue.config.js',
            'babel.config.js',
            'jest.config.js',
            'build',
        ];

        $found = [];
        foreach ($removedFiles as $file) {
            $fullPath = $projectDir . '/' . $file;
            if (file_exists($fullPath) || is_dir($fullPath)) {
                $found[] = $file;
            }
        }

        if ($found === []) {
            $this->addCheck(
                name: 'Removed files',
                status: 'pass',
                detail: 'No obsolete files found',
            );
        } else {
            $this->addCheck(
                name: 'Removed files',
                status: 'fail',
                detail: 'Found obsolete files: ' . implode(', ', $found),
                hint: 'Remove the listed files/directories',
            );
        }
    }

    /**
     * Determine if a PHP version constraint string satisfies >= 8.5.
     *
     * Supports common constraint formats:
     * - ">=X.Y" — passes if X.Y >= 8.5
     * - "^X.Y" — passes if X >= 8 and X.Y >= 8.5
     * - "~X.Y" — passes if X >= 8 and X.Y >= 8.5
     * - ">X.Y" — passes if X.Y >= 8.4 (strictly greater means 8.5+ is included)
     *
     * Returns false for constraints that clearly allow PHP < 8.5.
     */
    public function phpConstraintSatisfies85(string $constraint): bool
    {
        $constraint = trim($constraint);

        // Handle ">=X.Y" or ">=X"
        if (preg_match('/^>=\s*(\d+)(?:\.(\d+))?/', $constraint, $m)) {
            $major = (int) $m[1];
            $minor = (int) ($m[2] ?? 0);
            return ($major > 8) || ($major === 8 && $minor >= 5);
        }

        // Handle ">X.Y" or ">X"
        if (preg_match('/^>\s*(\d+)(?:\.(\d+))?/', $constraint, $m)) {
            $major = (int) $m[1];
            $minor = (int) ($m[2] ?? 0);
            // >8.4 means 8.5+ is included; >8.5 means 8.6+ (still >= 8.5)
            return ($major > 8) || ($major === 8 && $minor >= 4);
        }

        // Handle "^X.Y" — allows >=X.Y and <(X+1).0
        if (preg_match('/^\^\s*(\d+)(?:\.(\d+))?/', $constraint, $m)) {
            $major = (int) $m[1];
            $minor = (int) ($m[2] ?? 0);
            // ^8.5 means >=8.5 <9.0 — OK
            // ^9.0 means >=9.0 <10.0 — OK (>= 8.5)
            // ^8.0 means >=8.0 <9.0 — allows 8.0-8.4, NOT OK
            return ($major > 8) || ($major === 8 && $minor >= 5);
        }

        // Handle "~X.Y" — allows >=X.Y and <X.(Y+1)
        if (preg_match('/^~\s*(\d+)(?:\.(\d+))?/', $constraint, $m)) {
            $major = (int) $m[1];
            $minor = (int) ($m[2] ?? 0);
            return ($major > 8) || ($major === 8 && $minor >= 5);
        }

        // Exact version "X.Y.Z" or "X.Y"
        if (preg_match('/^(\d+)(?:\.(\d+))?/', $constraint, $m)) {
            $major = (int) $m[1];
            $minor = (int) ($m[2] ?? 0);
            return ($major > 8) || ($major === 8 && $minor >= 5);
        }

        // Unknown format — fail safe
        return false;
    }

    /**
     * Scan source files for deprecated patterns.
     *
     * @param array<string, string> $patterns regex => description
     * @param list<string> $extensions file extensions to scan
     */
    public function scanFilesForPatterns(
        string $projectDir,
        string $checkName,
        array $extensions,
        array $patterns,
    ): void {
        $files = $this->findSourceFiles($projectDir, $extensions);

        $found = [];
        foreach ($files as $file) {
            $content = file_get_contents($file);
            if ($content === false) {
                continue;
            }

            // Strip comments and strings to avoid false positives
            $codeOnly = $this->stripCommentsAndStrings($content, $file);

            foreach ($patterns as $regex => $description) {
                if (preg_match($regex, $codeOnly)) {
                    $relPath = $this->relativePath($projectDir, $file);
                    $found[] = "$relPath: $description";
                }
            }
        }

        if ($found === []) {
            $this->addCheck(
                name: $checkName,
                status: 'pass',
                detail: 'No deprecated patterns found',
            );
        } else {
            $this->addCheck(
                name: $checkName,
                status: 'fail',
                detail: 'Found deprecated patterns: ' . implode('; ', $found),
                hint: 'Update the listed files to use the new API',
            );
        }
    }

    /**
     * Strip single-line comments, multi-line comments, and string literals
     * from source code to avoid false positives in pattern detection.
     */
    public function stripCommentsAndStrings(string $content, string $filePath = ''): string
    {
        $ext = pathinfo($filePath, PATHINFO_EXTENSION);

        // For PHP files, use token_get_all for accurate stripping
        if ($ext === 'php') {
            return $this->stripPhpCommentsAndStrings($content);
        }

        // For JS/Vue/TS files, use regex-based stripping
        return $this->stripJsCommentsAndStrings($content);
    }

    /**
     * Strip comments and strings from PHP source using tokenizer.
     */
    private function stripPhpCommentsAndStrings(string $content): string
    {
        $tokens = @token_get_all($content);
        $result = '';
        foreach ($tokens as $token) {
            if (is_array($token)) {
                $type = $token[0];
                if (in_array($type, [T_COMMENT, T_DOC_COMMENT, T_CONSTANT_ENCAPSED_STRING, T_ENCAPSED_AND_WHITESPACE], true)) {
                    // Replace with whitespace to preserve line structure
                    $result .= str_repeat("\n", substr_count($token[1], "\n"));
                    continue;
                }
                $result .= $token[1];
            } else {
                $result .= $token;
            }
        }
        return $result;
    }

    /**
     * Strip comments and strings from JS/Vue/TS source using regex.
     *
     * This is a best-effort approach — not a full parser, but sufficient
     * for detecting deprecated API patterns without false positives.
     */
    private function stripJsCommentsAndStrings(string $content): string
    {
        // Remove multi-line comments /* ... */
        $content = preg_replace('/\/\*[\s\S]*?\*\//', '', $content) ?? $content;
        // Remove single-line comments // ...
        $content = preg_replace('/\/\/[^\n]*/', '', $content) ?? $content;
        // Remove template literals ` ... `
        $content = preg_replace('/`[^`]*`/', '""', $content) ?? $content;
        // Remove double-quoted strings
        $content = preg_replace('/"(?:[^"\\\\]|\\\\.)*"/', '""', $content) ?? $content;
        // Remove single-quoted strings
        $content = preg_replace("/\'(?:[^\'\\\\]|\\\\.)*\'/", "''", $content) ?? $content;

        return $content;
    }

    /**
     * Find source files with given extensions in a directory (recursive).
     *
     * @param list<string> $extensions
     * @return list<string>
     */
    public function findSourceFiles(string $dir, array $extensions): array
    {
        if (!is_dir($dir)) {
            return [];
        }

        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }
            $ext = $file->getExtension();
            if (in_array($ext, $extensions, true)) {
                // Skip vendor and node_modules directories
                $path = $file->getPathname();
                if (str_contains($path, '/vendor/') || str_contains($path, '/node_modules/')) {
                    continue;
                }
                $files[] = $path;
            }
        }

        return $files;
    }

    /**
     * Get the list of accumulated checks.
     *
     * @return list<array{name: string, status: string, detail: string, hint?: string}>
     */
    public function getChecks(): array
    {
        return $this->checks;
    }

    private function addCheck(string $name, string $status, string $detail, ?string $hint = null): void
    {
        $check = [
            'name'   => $name,
            'status' => $status,
            'detail' => $detail,
        ];
        if ($hint !== null) {
            $check['hint'] = $hint;
        }
        $this->checks[] = $check;
    }

    private function relativePath(string $base, string $path): string
    {
        $base = rtrim(realpath($base) ?: $base, '/') . '/';
        $real = realpath($path) ?: $path;
        if (str_starts_with($real, $base)) {
            return substr($real, strlen($base));
        }
        return $path;
    }
}
