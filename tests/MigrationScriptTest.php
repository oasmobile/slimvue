<?php

namespace Oasis\SlimVue\Tests;

use Eris\Generators;
use Eris\TestTrait;
use Oasis\SlimVue\MigrationScript;
use PHPUnit\Framework\TestCase;

class MigrationScriptTest extends TestCase
{
    use TestTrait;

    private string $tmpDir;
    private MigrationScript $script;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/slimvue-migrate-script-test-' . uniqid();
        mkdir($this->tmpDir, 0755, true);
        $this->script = new MigrationScript();
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->tmpDir);
    }

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

    private function writeJson(string $path, array $data): void
    {
        file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    // ── Backup tests ──

    public function testBackupFileCreatesBackup(): void
    {
        $filePath = $this->tmpDir . '/test.txt';
        file_put_contents($filePath, 'original content');

        $result = $this->script->backupFile($filePath);

        $this->assertTrue($result);
        $this->assertFileExists($filePath . '.bak');
    }

    public function testBackupFileNonExistent(): void
    {
        $filePath = $this->tmpDir . '/nonexistent.txt';

        $result = $this->script->backupFile($filePath);

        $this->assertFalse($result);
        $this->assertFileDoesNotExist($filePath . '.bak');
    }

    public function testBackupFileContentMatches(): void
    {
        $filePath = $this->tmpDir . '/test.txt';
        $content = "line1\nline2\nline3\n";
        file_put_contents($filePath, $content);

        $this->script->backupFile($filePath);

        $this->assertSame($content, file_get_contents($filePath . '.bak'));
    }

    // ── composer.json update tests ──

    public function testUpdateComposerJsonPhpConstraint(): void
    {
        $this->writeJson($this->tmpDir . '/composer.json', [
            'require' => ['php' => '>=7.0'],
        ]);

        $this->script->updateComposerJson($this->tmpDir);

        $data = json_decode(file_get_contents($this->tmpDir . '/composer.json'), true);
        $this->assertSame('>=8.5', $data['require']['php']);

        // Verify log contains the change
        $log = $this->script->getLog();
        $changeEntries = array_filter($log, fn(array $e): bool =>
            $e['action'] === 'Update PHP constraint' && $e['type'] === 'change'
        );
        $this->assertNotEmpty($changeEntries);
    }

    public function testUpdateComposerJsonReplaceSilex(): void
    {
        $this->writeJson($this->tmpDir . '/composer.json', [
            'require' => ['php' => '>=8.5'],
            'require-dev' => ['silex/silex' => '^2.2'],
        ]);

        $this->script->updateComposerJson($this->tmpDir);

        $data = json_decode(file_get_contents($this->tmpDir . '/composer.json'), true);
        $this->assertArrayNotHasKey('silex/silex', $data['require-dev']);
        $this->assertSame('^3.1', $data['require']['oasis/http']);

        $log = $this->script->getLog();
        $silexEntries = array_filter($log, fn(array $e): bool =>
            $e['action'] === 'Replace silex/silex' && $e['type'] === 'change'
        );
        $this->assertNotEmpty($silexEntries);
    }

    public function testUpdateComposerJsonNoFile(): void
    {
        $this->script->updateComposerJson($this->tmpDir);

        $log = $this->script->getLog();
        $skipEntries = array_filter($log, fn(array $e): bool =>
            $e['action'] === 'Update composer.json' && $e['type'] === 'skip'
        );
        $this->assertNotEmpty($skipEntries);
    }

    public function testUpdateComposerJsonInvalidJson(): void
    {
        file_put_contents($this->tmpDir . '/composer.json', 'not valid json {{{');

        $this->script->updateComposerJson($this->tmpDir);

        $log = $this->script->getLog();
        $errorEntries = array_filter($log, fn(array $e): bool =>
            $e['action'] === 'Update composer.json' && $e['type'] === 'error'
        );
        $this->assertNotEmpty($errorEntries);
    }

    public function testUpdateComposerJsonAlreadyUpToDate(): void
    {
        $this->writeJson($this->tmpDir . '/composer.json', [
            'require' => ['php' => '>=8.5'],
        ]);

        $this->script->updateComposerJson($this->tmpDir);

        $log = $this->script->getLog();
        $skipEntries = array_filter($log, fn(array $e): bool =>
            $e['action'] === 'Update composer.json' && $e['type'] === 'skip'
        );
        $this->assertNotEmpty($skipEntries);
    }

    public function testUpdateComposerJsonAddsPhpConstraint(): void
    {
        $this->writeJson($this->tmpDir . '/composer.json', [
            'require' => ['some/package' => '^1.0'],
        ]);

        $this->script->updateComposerJson($this->tmpDir);

        $data = json_decode(file_get_contents($this->tmpDir . '/composer.json'), true);
        $this->assertSame('>=8.5', $data['require']['php']);

        $log = $this->script->getLog();
        $addEntries = array_filter($log, fn(array $e): bool =>
            $e['action'] === 'Update PHP constraint' && $e['type'] === 'change'
            && str_contains($e['detail'], 'Added')
        );
        $this->assertNotEmpty($addEntries);
    }

    // ── Obsolete file removal tests ──

    public function testRemoveObsoleteFilesAllPresent(): void
    {
        touch($this->tmpDir . '/vue.config.js');
        touch($this->tmpDir . '/babel.config.js');
        touch($this->tmpDir . '/jest.config.js');
        touch($this->tmpDir . '/.eslintrc.js');
        mkdir($this->tmpDir . '/build', 0755, true);
        touch($this->tmpDir . '/build/somefile.js');

        $this->script->removeObsoleteFiles($this->tmpDir);

        $this->assertFileDoesNotExist($this->tmpDir . '/vue.config.js');
        $this->assertFileDoesNotExist($this->tmpDir . '/babel.config.js');
        $this->assertFileDoesNotExist($this->tmpDir . '/jest.config.js');
        $this->assertFileDoesNotExist($this->tmpDir . '/.eslintrc.js');
        $this->assertDirectoryDoesNotExist($this->tmpDir . '/build');

        $log = $this->script->getLog();
        $changeEntries = array_filter($log, fn(array $e): bool => $e['type'] === 'change');
        // 4 files + 1 directory = 5 changes
        $this->assertCount(5, $changeEntries);
    }

    public function testRemoveObsoleteFilesNonePresent(): void
    {
        $this->script->removeObsoleteFiles($this->tmpDir);

        $log = $this->script->getLog();
        $skipEntries = array_filter($log, fn(array $e): bool => $e['type'] === 'skip');
        // 4 files + 1 directory = 5 skips
        $this->assertCount(5, $skipEntries);
    }

    public function testRemoveObsoleteFilesBuildDirectory(): void
    {
        mkdir($this->tmpDir . '/build/sub', 0755, true);
        touch($this->tmpDir . '/build/file1.js');
        touch($this->tmpDir . '/build/sub/file2.js');

        $this->script->removeObsoleteFiles($this->tmpDir);

        $this->assertDirectoryDoesNotExist($this->tmpDir . '/build');

        $log = $this->script->getLog();
        $dirChange = array_filter($log, fn(array $e): bool =>
            $e['action'] === 'Remove obsolete directory' && $e['type'] === 'change'
        );
        $this->assertNotEmpty($dirChange);
    }

    public function testRemoveObsoleteFilesPartialPresent(): void
    {
        touch($this->tmpDir . '/vue.config.js');
        touch($this->tmpDir . '/jest.config.js');
        // babel.config.js, .eslintrc.js, build/ are absent

        $this->script->removeObsoleteFiles($this->tmpDir);

        $this->assertFileDoesNotExist($this->tmpDir . '/vue.config.js');
        $this->assertFileDoesNotExist($this->tmpDir . '/jest.config.js');

        $log = $this->script->getLog();
        $changeEntries = array_filter($log, fn(array $e): bool => $e['type'] === 'change');
        $skipEntries = array_filter($log, fn(array $e): bool => $e['type'] === 'skip');
        $this->assertCount(2, $changeEntries);
        $this->assertCount(3, $skipEntries);
    }

    // ── package.json scripts update tests ──

    public function testUpdatePackageJsonScriptsVueCli(): void
    {
        $this->writeJson($this->tmpDir . '/package.json', [
            'scripts' => [
                'serve' => 'vue-cli-service serve --mode serve',
                'build' => 'vue-cli-service build --mode development',
            ],
        ]);

        $this->script->updatePackageJsonScripts($this->tmpDir);

        $data = json_decode(file_get_contents($this->tmpDir . '/package.json'), true);
        $this->assertSame('vite', $data['scripts']['serve']);
        $this->assertSame(
            'node scripts/check-node.js && vite build --mode development',
            $data['scripts']['build'],
        );
    }

    public function testUpdatePackageJsonScriptsNoFile(): void
    {
        $this->script->updatePackageJsonScripts($this->tmpDir);

        $log = $this->script->getLog();
        $skipEntries = array_filter($log, fn(array $e): bool =>
            $e['action'] === 'Update package.json scripts' && $e['type'] === 'skip'
        );
        $this->assertNotEmpty($skipEntries);
    }

    public function testUpdatePackageJsonScriptsNoScripts(): void
    {
        $this->writeJson($this->tmpDir . '/package.json', [
            'name' => 'test-project',
        ]);

        $this->script->updatePackageJsonScripts($this->tmpDir);

        $log = $this->script->getLog();
        $skipEntries = array_filter($log, fn(array $e): bool =>
            $e['action'] === 'Update package.json scripts' && $e['type'] === 'skip'
            && str_contains($e['detail'], 'No scripts section')
        );
        $this->assertNotEmpty($skipEntries);
    }

    public function testUpdatePackageJsonScriptsAlreadyVite(): void
    {
        $this->writeJson($this->tmpDir . '/package.json', [
            'scripts' => [
                'dev' => 'vite',
                'build' => 'vite build',
            ],
        ]);

        $this->script->updatePackageJsonScripts($this->tmpDir);

        $log = $this->script->getLog();
        $skipEntries = array_filter($log, fn(array $e): bool =>
            $e['action'] === 'Update package.json scripts' && $e['type'] === 'skip'
            && str_contains($e['detail'], 'No script changes needed')
        );
        $this->assertNotEmpty($skipEntries);
    }

    public function testUpdatePackageJsonScriptsJestToVitest(): void
    {
        $this->writeJson($this->tmpDir . '/package.json', [
            'scripts' => [
                'test' => 'jest --no-cache',
                'test:coverage' => 'jest --no-cache --coverage',
            ],
        ]);

        $this->script->updatePackageJsonScripts($this->tmpDir);

        $data = json_decode(file_get_contents($this->tmpDir . '/package.json'), true);
        $this->assertSame('vitest run', $data['scripts']['test']);
        $this->assertSame('vitest run --coverage', $data['scripts']['test:coverage']);
    }

    // ── Script mapping tests ──

    public function testReplaceScriptValueServe(): void
    {
        $mappings = $this->script->getScriptMappings();
        $result = $this->script->replaceScriptValue('vue-cli-service serve --mode serve', $mappings);
        $this->assertSame('vite', $result);
    }

    public function testReplaceScriptValueBuild(): void
    {
        $mappings = $this->script->getScriptMappings();
        $result = $this->script->replaceScriptValue('vue-cli-service build --mode development', $mappings);
        $this->assertSame('node scripts/check-node.js && vite build --mode development', $result);
    }

    public function testReplaceScriptValueRelease(): void
    {
        $mappings = $this->script->getScriptMappings();
        $result = $this->script->replaceScriptValue('vue-cli-service build --modern', $mappings);
        $this->assertSame('node scripts/check-node.js && vite build', $result);
    }

    public function testReplaceScriptValueLint(): void
    {
        $mappings = $this->script->getScriptMappings();
        $result = $this->script->replaceScriptValue('vue-cli-service lint', $mappings);
        $this->assertSame('eslint .', $result);
    }

    public function testReplaceScriptValueTestCoverage(): void
    {
        $mappings = $this->script->getScriptMappings();
        $result = $this->script->replaceScriptValue('jest --no-cache --coverage', $mappings);
        $this->assertSame('vitest run --coverage', $result);
    }

    public function testReplaceScriptValueTest(): void
    {
        $mappings = $this->script->getScriptMappings();
        $result = $this->script->replaceScriptValue('jest --no-cache', $mappings);
        $this->assertSame('vitest run', $result);
    }

    public function testReplaceScriptValueNoMatch(): void
    {
        $mappings = $this->script->getScriptMappings();
        $result = $this->script->replaceScriptValue('echo hello', $mappings);
        $this->assertSame('echo hello', $result);
    }

    // ── JS transform tests (regex fallback) ──

    public function testTransformJsFilesNoFiles(): void
    {
        // Empty directory — no JS files
        $this->script->transformJsFiles($this->tmpDir);

        $log = $this->script->getLog();
        $skipEntries = array_filter($log, fn(array $e): bool =>
            $e['action'] === 'Transform JS files' && $e['type'] === 'skip'
        );
        $this->assertNotEmpty($skipEntries);
    }

    public function testFindJsFilesRecursive(): void
    {
        mkdir($this->tmpDir . '/src/components', 0755, true);
        touch($this->tmpDir . '/app.js');
        touch($this->tmpDir . '/src/main.ts');
        touch($this->tmpDir . '/src/components/App.vue');
        touch($this->tmpDir . '/readme.txt');

        $files = $this->script->findJsFiles($this->tmpDir);

        $this->assertCount(3, $files);
        $extensions = array_map(fn(string $f): string => pathinfo($f, PATHINFO_EXTENSION), $files);
        sort($extensions);
        $this->assertSame(['js', 'ts', 'vue'], $extensions);
    }

    public function testFindJsFilesSkipsVendor(): void
    {
        mkdir($this->tmpDir . '/vendor/pkg', 0755, true);
        touch($this->tmpDir . '/vendor/pkg/lib.js');
        touch($this->tmpDir . '/app.js');

        $files = $this->script->findJsFiles($this->tmpDir);

        $this->assertCount(1, $files);
        $this->assertStringContainsString('app.js', $files[0]);
    }

    public function testFindJsFilesSkipsNodeModules(): void
    {
        mkdir($this->tmpDir . '/node_modules/vue', 0755, true);
        touch($this->tmpDir . '/node_modules/vue/index.js');
        touch($this->tmpDir . '/main.js');

        $files = $this->script->findJsFiles($this->tmpDir);

        $this->assertCount(1, $files);
        $this->assertStringContainsString('main.js', $files[0]);
    }

    // ── Deprecated NPM deps flagging ──

    public function testFlagDeprecatedNpmDeps(): void
    {
        $this->writeJson($this->tmpDir . '/package.json', [
            'scripts' => [
                'serve' => 'vue-cli-service serve --mode serve',
            ],
            'dependencies' => [
                'vue' => '^2.6.11',
            ],
            'devDependencies' => [
                '@vue/cli-service' => '^4.0',
                'jest' => '^26.0',
                'vue-template-compiler' => '^2.6.11',
                'babel-core' => '^7.0',
            ],
        ]);

        $this->script->updatePackageJsonScripts($this->tmpDir);

        $log = $this->script->getLog();
        $manualEntries = array_filter($log, fn(array $e): bool =>
            $e['action'] === 'Deprecated dependency' && $e['type'] === 'manual'
        );

        // Should flag: vue ^2, @vue/cli-service, jest, vue-template-compiler, babel-core
        $this->assertGreaterThanOrEqual(5, count($manualEntries));

        $details = array_map(fn(array $e): string => $e['detail'], array_values($manualEntries));
        $allDetails = implode(' | ', $details);
        $this->assertStringContainsString('vue', $allDetails);
        $this->assertStringContainsString('@vue/cli-service', $allDetails);
        $this->assertStringContainsString('jest', $allDetails);
        $this->assertStringContainsString('vue-template-compiler', $allDetails);
        $this->assertStringContainsString('babel-core', $allDetails);
    }

    // ── Full migration integration ──

    public function testMigrateFullV3Project(): void
    {
        // Create a realistic v3 project structure
        $this->writeJson($this->tmpDir . '/composer.json', [
            'require' => ['php' => '>=7.0'],
            'require-dev' => ['silex/silex' => '^2.2'],
        ]);
        $this->writeJson($this->tmpDir . '/package.json', [
            'scripts' => [
                'serve' => 'vue-cli-service serve --mode serve',
                'build' => 'vue-cli-service build --mode development',
                'release' => 'vue-cli-service build --modern',
                'lint' => 'vue-cli-service lint',
                'test' => 'jest --no-cache',
                'test:coverage' => 'jest --no-cache --coverage',
            ],
            'dependencies' => [
                'vue' => '^2.6.11',
            ],
            'devDependencies' => [
                '@vue/cli-service' => '^4.0',
                'jest' => '^26.0',
            ],
        ]);
        touch($this->tmpDir . '/vue.config.js');
        touch($this->tmpDir . '/babel.config.js');
        touch($this->tmpDir . '/jest.config.js');
        touch($this->tmpDir . '/.eslintrc.js');
        mkdir($this->tmpDir . '/build', 0755, true);
        touch($this->tmpDir . '/build/webpack.config.js');

        // Create a JS file with Vue 2 patterns
        mkdir($this->tmpDir . '/src', 0755, true);
        file_put_contents($this->tmpDir . '/src/main.js', <<<'JS'
import Vue from 'vue';
const vm = new Vue({
    el: '#app',
});
Vue.prototype.$http = axios;
JS);

        $log = $this->script->migrate($this->tmpDir);

        // Verify composer.json updated
        $composerData = json_decode(file_get_contents($this->tmpDir . '/composer.json'), true);
        $this->assertSame('>=8.5', $composerData['require']['php']);
        $this->assertArrayNotHasKey('silex/silex', $composerData['require-dev']);
        $this->assertSame('^3.1', $composerData['require']['oasis/http']);

        // Verify obsolete files removed
        $this->assertFileDoesNotExist($this->tmpDir . '/vue.config.js');
        $this->assertFileDoesNotExist($this->tmpDir . '/babel.config.js');
        $this->assertFileDoesNotExist($this->tmpDir . '/jest.config.js');
        $this->assertFileDoesNotExist($this->tmpDir . '/.eslintrc.js');
        $this->assertDirectoryDoesNotExist($this->tmpDir . '/build');

        // Verify package.json scripts updated
        $packageData = json_decode(file_get_contents($this->tmpDir . '/package.json'), true);
        $this->assertSame('vite', $packageData['scripts']['serve']);
        $this->assertSame('vitest run', $packageData['scripts']['test']);
        $this->assertSame('vitest run --coverage', $packageData['scripts']['test:coverage']);
        $this->assertSame('eslint .', $packageData['scripts']['lint']);

        // Verify JS file was transformed (regex fallback)
        $jsContent = file_get_contents($this->tmpDir . '/src/main.js');
        $this->assertStringContainsString('createApp(', $jsContent);
        $this->assertStringContainsString('app.config.globalProperties', $jsContent);
        $this->assertStringNotContainsString('new Vue(', $jsContent);
        $this->assertStringNotContainsString('Vue.prototype', $jsContent);

        // Verify log has entries
        $this->assertNotEmpty($log);

        // Verify log contains changes, not just skips
        $changes = array_filter($log, fn(array $e): bool => $e['type'] === 'change');
        $this->assertNotEmpty($changes);
    }

    public function testMigrateAlreadyMigratedProject(): void
    {
        // Create a project that's already migrated
        $this->writeJson($this->tmpDir . '/composer.json', [
            'require' => ['php' => '>=8.5'],
        ]);
        $this->writeJson($this->tmpDir . '/package.json', [
            'scripts' => [
                'dev' => 'vite',
                'build' => 'vite build',
            ],
        ]);

        $log = $this->script->migrate($this->tmpDir);

        // Should mostly be skips — no changes needed
        $changes = array_filter($log, fn(array $e): bool => $e['type'] === 'change');
        $skips = array_filter($log, fn(array $e): bool => $e['type'] === 'skip');

        // The backup of composer.json and package.json are logged as 'change',
        // but the actual content updates should be skipped
        $contentSkips = array_filter($log, fn(array $e): bool =>
            $e['type'] === 'skip' && (
                str_contains($e['detail'], 'No changes needed')
                || str_contains($e['detail'], 'No script changes needed')
                || str_contains($e['detail'], 'not found, skipping')
                || str_contains($e['detail'], 'No JS/Vue files')
                || str_contains($e['detail'], 'No Vue 2 patterns')
            )
        );
        $this->assertNotEmpty($skips);
    }

    // ── Log structure ──

    public function testLogEntryStructure(): void
    {
        $filePath = $this->tmpDir . '/test.txt';
        file_put_contents($filePath, 'content');

        $this->script->backupFile($filePath);

        $log = $this->script->getLog();
        $this->assertNotEmpty($log);

        $entry = $log[0];
        $this->assertArrayHasKey('action', $entry);
        $this->assertArrayHasKey('detail', $entry);
        $this->assertArrayHasKey('type', $entry);
        $this->assertIsString($entry['action']);
        $this->assertIsString($entry['detail']);
        $this->assertContains($entry['type'], ['change', 'skip', 'warning', 'error', 'manual']);
    }

    // ── PBT: Feature: release-4.0 ──

    /**
     * Feature: release-4.0, Property 16: config file migration
     *
     * For any composer.json (with arbitrary PHP version constraint and
     * arbitrary deprecated dependencies) and package.json (with arbitrary
     * Vue CLI scripts), the migration script should:
     * (a) update PHP version constraint to >= 8.5
     * (b) replace deprecated dependencies with new equivalents
     * (c) update Vue CLI commands to Vite commands
     *
     * **Validates: Requirements 16.1, 16.2, 16.5**
     */
    public function testPbtConfigFileMigration(): void
    {
        $phpConstraintGen = Generators::elements([
            '>=7.0', '>=7.4', '>=8.0', '^7.4', '^8.0', '>=8.5', '^8.5',
        ]);

        $hasSilexGen = Generators::bool();

        $scriptSetGen = Generators::elements([
            // Vue CLI scripts
            [
                'serve' => 'vue-cli-service serve --mode serve',
                'build' => 'vue-cli-service build --mode development',
                'release' => 'vue-cli-service build --modern',
                'lint' => 'vue-cli-service lint',
                'test' => 'jest --no-cache',
                'test:coverage' => 'jest --no-cache --coverage',
            ],
            // Already Vite scripts
            [
                'dev' => 'vite',
                'build' => 'vite build',
                'test' => 'vitest run',
            ],
            // Mixed scripts
            [
                'serve' => 'vue-cli-service serve --mode serve',
                'custom' => 'echo hello',
                'test' => 'jest --no-cache',
            ],
            // Empty scripts
            [],
        ]);

        $this->limitTo(50);
        $this->forAll($phpConstraintGen, $hasSilexGen, $scriptSetGen)->then(
            function (string $phpConstraint, bool $hasSilex, array $scripts): void {
                $tmpDir = $this->tmpDir . '/pbt16-' . uniqid();
                mkdir($tmpDir, 0755, true);

                // Create composer.json
                $composerData = ['require' => ['php' => $phpConstraint]];
                if ($hasSilex) {
                    $composerData['require-dev'] = ['silex/silex' => '^2.2'];
                }
                $this->writeJson($tmpDir . '/composer.json', $composerData);

                // Create package.json with scripts
                if ($scripts !== []) {
                    $this->writeJson($tmpDir . '/package.json', ['scripts' => $scripts]);
                }

                $script = new MigrationScript();
                $script->migrate($tmpDir);

                // (a) PHP version constraint should be >= 8.5 after migration
                $updatedComposer = json_decode(file_get_contents($tmpDir . '/composer.json'), true);
                $this->assertSame(
                    '>=8.5',
                    $updatedComposer['require']['php'],
                    "PHP constraint should be >=8.5 after migration (was: $phpConstraint)",
                );

                // (b) silex/silex should be replaced with oasis/http
                if ($hasSilex) {
                    $this->assertArrayNotHasKey(
                        'silex/silex',
                        $updatedComposer['require-dev'] ?? [],
                        'silex/silex should be removed',
                    );
                    $this->assertSame(
                        '^3.1',
                        $updatedComposer['require']['oasis/http'] ?? null,
                        'oasis/http should be added to require',
                    );
                }

                // (c) Vue CLI commands should be replaced with Vite commands
                if ($scripts !== [] && file_exists($tmpDir . '/package.json')) {
                    $updatedPkg = json_decode(file_get_contents($tmpDir . '/package.json'), true);
                    $updatedScripts = $updatedPkg['scripts'] ?? [];
                    foreach ($updatedScripts as $value) {
                        $this->assertStringNotContainsString(
                            'vue-cli-service',
                            $value,
                            'No vue-cli-service references should remain in scripts',
                        );
                        $this->assertStringNotContainsString(
                            'jest --no-cache',
                            $value,
                            'No jest references should remain in scripts',
                        );
                    }
                }

                $this->removeDir($tmpDir);
            },
        );
    }

    /**
     * Feature: release-4.0, Property 17: obsolete file removal
     *
     * For any project directory (with an arbitrary subset of obsolete files
     * present), the migration script should remove all specified obsolete
     * files and NOT affect other files.
     *
     * **Validates: Requirements 16.3**
     */
    public function testPbtObsoleteFileRemoval(): void
    {
        $obsoleteFiles = ['vue.config.js', 'babel.config.js', 'jest.config.js', '.eslintrc.js', 'build'];
        $subsetGen = Generators::subset($obsoleteFiles);

        // Extra files that should NOT be removed
        $extraFileGen = Generators::elements([
            'index.js', 'app.vue', 'package.json', 'README.md', 'src/main.js',
        ]);

        $this->limitTo(50);
        $this->forAll($subsetGen, $extraFileGen)->then(
            function (array $presentFiles, string $extraFile): void {
                $obsoleteFiles = ['vue.config.js', 'babel.config.js', 'jest.config.js', '.eslintrc.js', 'build'];
                $tmpDir = $this->tmpDir . '/pbt17-' . uniqid();
                mkdir($tmpDir, 0755, true);

                // Create the selected subset of obsolete files
                foreach ($presentFiles as $file) {
                    $path = $tmpDir . '/' . $file;
                    if ($file === 'build') {
                        mkdir($path, 0755, true);
                        touch($path . '/webpack.config.js');
                    } else {
                        touch($path);
                    }
                }

                // Create an extra file that should NOT be removed
                $extraPath = $tmpDir . '/' . $extraFile;
                $extraDir = dirname($extraPath);
                if (!is_dir($extraDir)) {
                    mkdir($extraDir, 0755, true);
                }
                file_put_contents($extraPath, 'keep me');

                $script = new MigrationScript();
                $script->removeObsoleteFiles($tmpDir);

                // All obsolete files that were present should be removed
                foreach ($presentFiles as $file) {
                    $path = $tmpDir . '/' . $file;
                    $this->assertFalse(
                        file_exists($path) || is_dir($path),
                        "Obsolete file '$file' should be removed",
                    );
                }

                // Extra file should still exist
                $this->assertFileExists(
                    $extraPath,
                    "Non-obsolete file '$extraFile' should NOT be removed",
                );
                $this->assertSame(
                    'keep me',
                    file_get_contents($extraPath),
                    "Non-obsolete file '$extraFile' content should be preserved",
                );

                $this->removeDir($tmpDir);
            },
        );
    }

    /**
     * Feature: release-4.0, Property 19: backup creation
     *
     * For any set of files that will be modified, the migration script
     * should create .bak backups before modification, and the backup
     * content should be identical to the original.
     *
     * **Validates: Requirements 16.6**
     */
    public function testPbtBackupCreation(): void
    {
        // Generate random file content
        $contentGen = Generators::elements([
            '{"require":{"php":">=7.0"}}',
            '{"require":{"php":">=8.0"},"require-dev":{"silex/silex":"^2.2"}}',
            '{"name":"test","scripts":{"serve":"vue-cli-service serve --mode serve"}}',
            "line1\nline2\nline3",
            '',
            '中文内容测试',
            str_repeat('a', 1000),
        ]);

        // Generate random file names
        $fileNameGen = Generators::elements([
            'composer.json', 'package.json', 'config.txt', 'data.json', 'readme.md',
        ]);

        $this->limitTo(50);
        $this->forAll($contentGen, $fileNameGen)->then(
            function (string $content, string $fileName): void {
                $tmpDir = $this->tmpDir . '/pbt19-' . uniqid();
                mkdir($tmpDir, 0755, true);

                $filePath = $tmpDir . '/' . $fileName;
                file_put_contents($filePath, $content);

                $script = new MigrationScript();
                $result = $script->backupFile($filePath);

                // Backup should succeed
                $this->assertTrue($result, "Backup of '$fileName' should succeed");

                // Backup file should exist
                $backupPath = $filePath . '.bak';
                $this->assertFileExists($backupPath, "Backup file should exist");

                // Backup content should be identical to original
                $this->assertSame(
                    $content,
                    file_get_contents($backupPath),
                    "Backup content should be identical to original for '$fileName'",
                );

                // Original file should still exist and be unchanged
                $this->assertFileExists($filePath, "Original file should still exist");
                $this->assertSame(
                    $content,
                    file_get_contents($filePath),
                    "Original file content should be unchanged",
                );

                $this->removeDir($tmpDir);
            },
        );
    }
}
