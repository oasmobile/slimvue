<?php

namespace Oasis\SlimVue;

class MigrationScript
{
    /** @var list<array{action: string, detail: string, type: string}> */
    private array $log = [];

    /**
     * Run the full migration on the given project directory.
     *
     * @return list<array{action: string, detail: string, type: string}>
     */
    public function migrate(string $projectDir): array
    {
        $this->log = [];

        $this->updateComposerJson($projectDir);
        $this->removeObsoleteFiles($projectDir);
        $this->transformJsFiles($projectDir);
        $this->updatePackageJsonScripts($projectDir);

        return $this->log;
    }

    /**
     * Create a .bak backup of the given file.
     *
     * Returns true on success, false on failure.
     */
    public function backupFile(string $filePath): bool
    {
        if (!file_exists($filePath)) {
            return false;
        }

        $backupPath = $filePath . '.bak';
        if (!copy($filePath, $backupPath)) {
            $this->addLog(
                action: 'Backup ' . basename($filePath),
                detail: "Failed to create backup: $backupPath",
                type: 'error',
            );
            return false;
        }

        $this->addLog(
            action: 'Backup ' . basename($filePath),
            detail: "Created backup: $backupPath",
            type: 'change',
        );
        return true;
    }

    /**
     * Update composer.json: PHP version constraint → >= 8.5, replace deprecated deps.
     */
    public function updateComposerJson(string $projectDir): void
    {
        $composerPath = $projectDir . '/composer.json';
        if (!file_exists($composerPath)) {
            $this->addLog(
                action: 'Update composer.json',
                detail: 'composer.json not found, skipping',
                type: 'skip',
            );
            return;
        }

        if (!$this->backupFile($composerPath)) {
            $this->addLog(
                action: 'Update composer.json',
                detail: 'Failed to backup composer.json, aborting composer.json update',
                type: 'error',
            );
            return;
        }

        $data = json_decode((string) file_get_contents($composerPath), true);
        if (!is_array($data)) {
            $this->addLog(
                action: 'Update composer.json',
                detail: 'composer.json is not valid JSON',
                type: 'error',
            );
            return;
        }

        $changed = false;

        // Update PHP version constraint
        if (isset($data['require']['php'])) {
            $oldConstraint = $data['require']['php'];
            $data['require']['php'] = '>=8.5';
            if ($oldConstraint !== '>=8.5') {
                $this->addLog(
                    action: 'Update PHP constraint',
                    detail: "Changed require.php from '$oldConstraint' to '>=8.5'",
                    type: 'change',
                );
                $changed = true;
            }
        } else {
            $data['require'] ??= [];
            $data['require'] = array_merge(['php' => '>=8.5'], $data['require']);
            $this->addLog(
                action: 'Update PHP constraint',
                detail: "Added require.php '>=8.5'",
                type: 'change',
            );
            $changed = true;
        }

        // Replace silex/silex with oasis/http in require
        if (isset($data['require-dev']['silex/silex'])) {
            $oldVersion = $data['require-dev']['silex/silex'];
            unset($data['require-dev']['silex/silex']);
            if (!isset($data['require'])) {
                $data['require'] = [];
            }
            $data['require']['oasis/http'] = '^3.1';
            $this->addLog(
                action: 'Replace silex/silex',
                detail: "Removed silex/silex ($oldVersion) from require-dev, added oasis/http ^3.1 to require",
                type: 'change',
            );
            $changed = true;
        }

        if ($changed) {
            file_put_contents(
                $composerPath,
                json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n",
            );
        } else {
            $this->addLog(
                action: 'Update composer.json',
                detail: 'No changes needed',
                type: 'skip',
            );
        }
    }

    /**
     * Remove obsolete files: vue.config.js, babel.config.js, jest.config.js, .eslintrc.js, build/.
     */
    public function removeObsoleteFiles(string $projectDir): void
    {
        $filesToRemove = [
            'vue.config.js',
            'babel.config.js',
            'jest.config.js',
            '.eslintrc.js',
        ];

        foreach ($filesToRemove as $file) {
            $fullPath = $projectDir . '/' . $file;
            if (file_exists($fullPath)) {
                unlink($fullPath);
                $this->addLog(
                    action: 'Remove obsolete file',
                    detail: "Removed $file",
                    type: 'change',
                );
            } else {
                $this->addLog(
                    action: 'Remove obsolete file',
                    detail: "$file not found, skipping",
                    type: 'skip',
                );
            }
        }

        // Remove build/ directory recursively
        $buildDir = $projectDir . '/build';
        if (is_dir($buildDir)) {
            $this->removeDir($buildDir);
            $this->addLog(
                action: 'Remove obsolete directory',
                detail: 'Removed build/ directory',
                type: 'change',
            );
        } else {
            $this->addLog(
                action: 'Remove obsolete directory',
                detail: 'build/ directory not found, skipping',
                type: 'skip',
            );
        }
    }

    /**
     * Transform JS files: call Node.js AST transform via exec(), fall back to regex if unavailable.
     */
    public function transformJsFiles(string $projectDir): void
    {
        $jsFiles = $this->findJsFiles($projectDir);
        if ($jsFiles === []) {
            $this->addLog(
                action: 'Transform JS files',
                detail: 'No JS/Vue files found to transform',
                type: 'skip',
            );
            return;
        }

        // Check if Node.js is available
        $nodeAvailable = $this->isNodeAvailable();

        if ($nodeAvailable) {
            $transformScript = $this->resolveTransformScriptPath();
            if ($transformScript !== null && file_exists($transformScript)) {
                $this->transformWithAst($projectDir, $jsFiles, $transformScript);
                return;
            }
            $this->addLog(
                action: 'Transform JS files',
                detail: 'AST transform script not found, falling back to regex replacement',
                type: 'warning',
            );
        } else {
            $this->addLog(
                action: 'Transform JS files',
                detail: 'Node.js not available, falling back to regex replacement. Manual review recommended',
                type: 'warning',
            );
        }

        // Regex fallback
        $this->transformWithRegex($projectDir, $jsFiles);
    }

    /**
     * Update package.json scripts: Vue CLI commands → Vite commands.
     */
    public function updatePackageJsonScripts(string $projectDir): void
    {
        $packagePath = $projectDir . '/package.json';
        if (!file_exists($packagePath)) {
            $this->addLog(
                action: 'Update package.json scripts',
                detail: 'package.json not found, skipping',
                type: 'skip',
            );
            return;
        }

        if (!$this->backupFile($packagePath)) {
            $this->addLog(
                action: 'Update package.json scripts',
                detail: 'Failed to backup package.json, aborting scripts update',
                type: 'error',
            );
            return;
        }

        $data = json_decode((string) file_get_contents($packagePath), true);
        if (!is_array($data)) {
            $this->addLog(
                action: 'Update package.json scripts',
                detail: 'package.json is not valid JSON',
                type: 'error',
            );
            return;
        }

        if (!isset($data['scripts']) || !is_array($data['scripts'])) {
            $this->addLog(
                action: 'Update package.json scripts',
                detail: 'No scripts section found in package.json',
                type: 'skip',
            );
            return;
        }

        $changed = false;
        $scriptMappings = $this->getScriptMappings();

        foreach ($data['scripts'] as $name => $value) {
            $newValue = $this->replaceScriptValue($value, $scriptMappings);
            if ($newValue !== $value) {
                $data['scripts'][$name] = $newValue;
                $this->addLog(
                    action: 'Update script',
                    detail: "Changed '$name': '$value' → '$newValue'",
                    type: 'change',
                );
                $changed = true;
            }
        }

        // Flag deprecated NPM dependencies for manual attention
        $this->flagDeprecatedNpmDeps($data);

        if ($changed) {
            file_put_contents(
                $packagePath,
                json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n",
            );
        } else {
            $this->addLog(
                action: 'Update package.json scripts',
                detail: 'No script changes needed',
                type: 'skip',
            );
        }
    }

    /**
     * @return list<array{action: string, detail: string, type: string}>
     */
    public function getLog(): array
    {
        return $this->log;
    }

    // ── Script mapping ──

    /**
     * Get the Vue CLI → Vite script value mappings.
     *
     * @return list<array{pattern: string, replacement: string}>
     */
    public function getScriptMappings(): array
    {
        return [
            [
                'pattern' => '#vue-cli-service\s+serve\s+--mode\s+serve#',
                'replacement' => 'vite',
            ],
            [
                'pattern' => '#vue-cli-service\s+build\s+--mode\s+development#',
                'replacement' => 'node scripts/check-node.js && vite build --mode development',
            ],
            [
                'pattern' => '#vue-cli-service\s+build\s+--modern#',
                'replacement' => 'node scripts/check-node.js && vite build',
            ],
            [
                'pattern' => '#vue-cli-service\s+lint#',
                'replacement' => 'eslint .',
            ],
            [
                'pattern' => '#jest\s+--no-cache\s+--coverage#',
                'replacement' => 'vitest run --coverage',
            ],
            [
                'pattern' => '#jest\s+--no-cache#',
                'replacement' => 'vitest run',
            ],
        ];
    }

    /**
     * Apply script mappings to a single script value.
     *
     * @param list<array{pattern: string, replacement: string}> $mappings
     */
    public function replaceScriptValue(string $value, array $mappings): string
    {
        foreach ($mappings as $mapping) {
            $result = preg_replace($mapping['pattern'], $mapping['replacement'], $value);
            if ($result !== null && $result !== $value) {
                return $result;
            }
        }
        return $value;
    }

    // ── JS transform helpers ──

    /**
     * Check if Node.js is available on the system.
     */
    public function isNodeAvailable(): bool
    {
        $output = [];
        $exitCode = 0;
        @exec('node --version 2>/dev/null', $output, $exitCode);
        return $exitCode === 0;
    }

    /**
     * Resolve the path to the AST transform script relative to the package root.
     */
    public function resolveTransformScriptPath(): ?string
    {
        // The transform script is at bin/transforms/vue2-to-vue3.js relative to the package root.
        // The package root is the parent of the bin/ directory where slimvue-migrate lives.
        $packageRoot = dirname(__DIR__);
        $scriptPath = $packageRoot . '/bin/transforms/vue2-to-vue3.js';
        return $scriptPath;
    }

    /**
     * Find JS/Vue files in the project directory for transformation.
     *
     * @return list<string>
     */
    public function findJsFiles(string $projectDir): array
    {
        if (!is_dir($projectDir)) {
            return [];
        }

        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($projectDir, \FilesystemIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }
            $ext = $file->getExtension();
            if (!in_array($ext, ['js', 'vue', 'ts', 'jsx', 'tsx'], true)) {
                continue;
            }
            $path = $file->getPathname();
            // Skip vendor and node_modules
            if (str_contains($path, '/vendor/') || str_contains($path, '/node_modules/')) {
                continue;
            }
            $files[] = $path;
        }

        return $files;
    }

    /**
     * Transform JS files using the Node.js AST transform script.
     *
     * @param list<string> $jsFiles
     */
    private function transformWithAst(string $projectDir, array $jsFiles, string $transformScript): void
    {
        foreach ($jsFiles as $file) {
            $this->backupFile($file);
        }

        $escapedScript = escapeshellarg($transformScript);
        $escapedDir = escapeshellarg($projectDir);

        $output = [];
        $exitCode = 0;
        exec(
            "node $escapedScript $escapedDir 2>&1",
            $output,
            $exitCode,
        );

        if ($exitCode === 0) {
            $this->addLog(
                action: 'AST transform',
                detail: 'Successfully transformed JS files using AST transform',
                type: 'change',
            );
        } else {
            $errorOutput = implode("\n", $output);
            $this->addLog(
                action: 'AST transform',
                detail: "AST transform failed (exit code $exitCode): $errorOutput. Falling back to regex",
                type: 'warning',
            );
            // Fall back to regex
            $this->transformWithRegex($projectDir, $jsFiles);
        }
    }

    /**
     * Transform JS files using regex replacement (fallback).
     *
     * @param list<string> $jsFiles
     */
    private function transformWithRegex(string $projectDir, array $jsFiles): void
    {
        $patterns = [
            [
                'pattern' => '/\bnew\s+Vue\s*\(/',
                'replacement' => 'createApp(',
                'description' => 'new Vue( → createApp(',
            ],
            [
                'pattern' => '/\bVue\.prototype\b/',
                'replacement' => 'app.config.globalProperties',
                'description' => 'Vue.prototype → app.config.globalProperties',
            ],
        ];

        $totalChanges = 0;
        foreach ($jsFiles as $file) {
            $content = file_get_contents($file);
            if ($content === false) {
                continue;
            }

            $newContent = $content;
            $fileChanges = [];
            foreach ($patterns as $p) {
                $count = 0;
                $result = preg_replace($p['pattern'], $p['replacement'], $newContent, -1, $count);
                if ($result === null) {
                    continue;
                }
                $newContent = $result;
                if ($count > 0) {
                    $fileChanges[] = $p['description'] . " ($count occurrence" . ($count > 1 ? 's' : '') . ')';
                }
            }

            if ($fileChanges !== []) {
                $this->backupFile($file);
                file_put_contents($file, $newContent);
                $relPath = $this->relativePath($projectDir, $file);
                $this->addLog(
                    action: 'Regex transform',
                    detail: "$relPath: " . implode('; ', $fileChanges),
                    type: 'change',
                );
                $totalChanges++;
            }
        }

        if ($totalChanges === 0) {
            $this->addLog(
                action: 'Regex transform',
                detail: 'No Vue 2 patterns found in JS files',
                type: 'skip',
            );
        }

        $this->addLog(
            action: 'JS transform notice',
            detail: 'Regex-based transform was used. Please manually review transformed files for correctness',
            type: 'manual',
        );
    }

    /**
     * Flag deprecated NPM dependencies that need manual attention.
     *
     * @param array<string, mixed> $packageData
     */
    private function flagDeprecatedNpmDeps(array $packageData): void
    {
        $deprecatedDeps = [
            'vue' => ['versionPrefix' => '^2', 'action' => 'Upgrade to vue ^3'],
            '@vue/cli-service' => ['versionPrefix' => null, 'action' => 'Remove (replace with vite)'],
            '@vue/cli-plugin-babel' => ['versionPrefix' => null, 'action' => 'Remove'],
            '@vue/cli-plugin-eslint' => ['versionPrefix' => null, 'action' => 'Remove'],
            'babel-core' => ['versionPrefix' => null, 'action' => 'Remove'],
            'babel-eslint' => ['versionPrefix' => null, 'action' => 'Remove'],
            'babel-jest' => ['versionPrefix' => null, 'action' => 'Remove'],
            'vue-template-compiler' => ['versionPrefix' => null, 'action' => 'Remove'],
            'vue-jest' => ['versionPrefix' => null, 'action' => 'Remove'],
            'sass-loader' => ['versionPrefix' => null, 'action' => 'Remove'],
            'jest' => ['versionPrefix' => null, 'action' => 'Replace with vitest'],
            '@vue/cli-plugin-unit-jest' => ['versionPrefix' => null, 'action' => 'Remove (replace with vitest)'],
        ];

        $allDeps = array_merge(
            $packageData['dependencies'] ?? [],
            $packageData['devDependencies'] ?? [],
        );

        foreach ($deprecatedDeps as $pkg => $info) {
            if (!array_key_exists($pkg, $allDeps)) {
                continue;
            }
            $version = $allDeps[$pkg];
            if ($info['versionPrefix'] !== null) {
                if (!is_string($version) || !str_starts_with($version, $info['versionPrefix'])) {
                    continue;
                }
            }
            $this->addLog(
                action: 'Deprecated dependency',
                detail: "$pkg — {$info['action']}",
                type: 'manual',
            );
        }
    }

    // ── Utility helpers ──

    private function addLog(string $action, string $detail, string $type): void
    {
        $this->log[] = [
            'action' => $action,
            'detail' => $detail,
            'type' => $type,
        ];
    }

    /**
     * Recursively remove a directory and its contents.
     */
    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($items as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }
        rmdir($dir);
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
