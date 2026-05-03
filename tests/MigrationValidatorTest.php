<?php

namespace Oasis\SlimVue\Tests;

use Eris\Generators;
use Eris\TestTrait;
use Oasis\SlimVue\MigrationValidator;
use PHPUnit\Framework\TestCase;

class MigrationValidatorTest extends TestCase
{
    use TestTrait;

    private string $tmpDir;
    private MigrationValidator $validator;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/slimvue-migrate-test-' . uniqid();
        mkdir($this->tmpDir, 0755, true);
        $this->validator = new MigrationValidator();
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

    // ── buildReport() ──

    public function testBuildReportCountsCorrectly(): void
    {
        $checks = [
            ['name' => 'A', 'status' => 'pass', 'detail' => 'ok'],
            ['name' => 'B', 'status' => 'fail', 'detail' => 'bad', 'hint' => 'fix it'],
            ['name' => 'C', 'status' => 'pass', 'detail' => 'ok'],
        ];
        $report = MigrationValidator::buildReport($checks);
        $this->assertSame(3, $report['summary']['total']);
        $this->assertSame(2, $report['summary']['passed']);
        $this->assertSame(1, $report['summary']['failed']);
        $this->assertCount(3, $report['checks']);
    }

    public function testBuildReportAllPass(): void
    {
        $checks = [
            ['name' => 'A', 'status' => 'pass', 'detail' => 'ok'],
        ];
        $report = MigrationValidator::buildReport($checks);
        $this->assertSame(0, $report['summary']['failed']);
    }

    public function testBuildReportEmpty(): void
    {
        $report = MigrationValidator::buildReport([]);
        $this->assertSame(0, $report['summary']['total']);
        $this->assertSame(0, $report['summary']['passed']);
        $this->assertSame(0, $report['summary']['failed']);
    }

    // ── PHP version constraint ──

    public function testPhpConstraintPassWithGte85(): void
    {
        $this->writeJson($this->tmpDir . '/composer.json', [
            'require' => ['php' => '>=8.5'],
        ]);
        $this->validator->checkPhpVersionConstraint($this->tmpDir);
        $checks = $this->validator->getChecks();
        $this->assertSame('pass', $checks[0]['status']);
    }

    public function testPhpConstraintPassWithGte90(): void
    {
        $this->writeJson($this->tmpDir . '/composer.json', [
            'require' => ['php' => '>=9.0'],
        ]);
        $this->validator->checkPhpVersionConstraint($this->tmpDir);
        $checks = $this->validator->getChecks();
        $this->assertSame('pass', $checks[0]['status']);
    }

    public function testPhpConstraintFailWithGte70(): void
    {
        $this->writeJson($this->tmpDir . '/composer.json', [
            'require' => ['php' => '>=7.0'],
        ]);
        $this->validator->checkPhpVersionConstraint($this->tmpDir);
        $checks = $this->validator->getChecks();
        $this->assertSame('fail', $checks[0]['status']);
        $this->assertArrayHasKey('hint', $checks[0]);
    }

    public function testPhpConstraintFailWithCaret80(): void
    {
        $this->writeJson($this->tmpDir . '/composer.json', [
            'require' => ['php' => '^8.0'],
        ]);
        $this->validator->checkPhpVersionConstraint($this->tmpDir);
        $checks = $this->validator->getChecks();
        $this->assertSame('fail', $checks[0]['status']);
    }

    public function testPhpConstraintPassWithCaret85(): void
    {
        $this->writeJson($this->tmpDir . '/composer.json', [
            'require' => ['php' => '^8.5'],
        ]);
        $this->validator->checkPhpVersionConstraint($this->tmpDir);
        $checks = $this->validator->getChecks();
        $this->assertSame('pass', $checks[0]['status']);
    }

    public function testPhpConstraintFailNoComposerJson(): void
    {
        $this->validator->checkPhpVersionConstraint($this->tmpDir);
        $checks = $this->validator->getChecks();
        $this->assertSame('fail', $checks[0]['status']);
        $this->assertStringContainsString('not found', $checks[0]['detail']);
    }

    public function testPhpConstraintFailInvalidJson(): void
    {
        file_put_contents($this->tmpDir . '/composer.json', 'not json');
        $this->validator->checkPhpVersionConstraint($this->tmpDir);
        $checks = $this->validator->getChecks();
        $this->assertSame('fail', $checks[0]['status']);
        $this->assertStringContainsString('not valid JSON', $checks[0]['detail']);
    }

    public function testPhpConstraintFailNoPhpField(): void
    {
        $this->writeJson($this->tmpDir . '/composer.json', [
            'require' => ['some/package' => '^1.0'],
        ]);
        $this->validator->checkPhpVersionConstraint($this->tmpDir);
        $checks = $this->validator->getChecks();
        $this->assertSame('fail', $checks[0]['status']);
        $this->assertStringContainsString('No PHP version constraint', $checks[0]['detail']);
    }

    // ── phpConstraintSatisfies85() ──

    public function testPhpConstraintSatisfies85Variants(): void
    {
        $v = $this->validator;
        // Should pass
        $this->assertTrue($v->phpConstraintSatisfies85('>=8.5'));
        $this->assertTrue($v->phpConstraintSatisfies85('>=9.0'));
        $this->assertTrue($v->phpConstraintSatisfies85('^8.5'));
        $this->assertTrue($v->phpConstraintSatisfies85('^9.0'));
        $this->assertTrue($v->phpConstraintSatisfies85('~8.5'));
        $this->assertTrue($v->phpConstraintSatisfies85('>8.4'));
        $this->assertTrue($v->phpConstraintSatisfies85('>8.5'));
        $this->assertTrue($v->phpConstraintSatisfies85('8.5'));
        $this->assertTrue($v->phpConstraintSatisfies85('9.0'));

        // Should fail
        $this->assertFalse($v->phpConstraintSatisfies85('>=7.0'));
        $this->assertFalse($v->phpConstraintSatisfies85('>=8.0'));
        $this->assertFalse($v->phpConstraintSatisfies85('^8.0'));
        $this->assertFalse($v->phpConstraintSatisfies85('^7.4'));
        $this->assertFalse($v->phpConstraintSatisfies85('~8.0'));
        $this->assertFalse($v->phpConstraintSatisfies85('>8.3'));
        $this->assertFalse($v->phpConstraintSatisfies85('7.4'));
        $this->assertFalse($v->phpConstraintSatisfies85('8.4'));
    }

    public function testPhpConstraintSatisfies85UnknownFormat(): void
    {
        $this->assertFalse($this->validator->phpConstraintSatisfies85('*'));
        $this->assertFalse($this->validator->phpConstraintSatisfies85(''));
    }

    // ── Deprecated dependencies ──

    public function testDeprecatedDepsPassCleanProject(): void
    {
        $this->writeJson($this->tmpDir . '/composer.json', [
            'require' => ['php' => '>=8.5', 'oasis/http' => '^3.0'],
        ]);
        $this->writeJson($this->tmpDir . '/package.json', [
            'dependencies' => ['vue' => '^3.0'],
            'devDependencies' => ['vitest' => '^1.0'],
        ]);
        $this->validator->checkDeprecatedDependencies($this->tmpDir);
        $checks = $this->validator->getChecks();
        $this->assertSame('pass', $checks[0]['status']);
    }

    public function testDeprecatedDepsFailSilex(): void
    {
        $this->writeJson($this->tmpDir . '/composer.json', [
            'require-dev' => ['silex/silex' => '^2.2'],
        ]);
        $this->validator->checkDeprecatedDependencies($this->tmpDir);
        $checks = $this->validator->getChecks();
        $this->assertSame('fail', $checks[0]['status']);
        $this->assertStringContainsString('silex/silex', $checks[0]['detail']);
    }

    public function testDeprecatedDepsFailVue2(): void
    {
        $this->writeJson($this->tmpDir . '/composer.json', ['require' => []]);
        $this->writeJson($this->tmpDir . '/package.json', [
            'dependencies' => ['vue' => '^2.6.11'],
        ]);
        $this->validator->checkDeprecatedDependencies($this->tmpDir);
        $checks = $this->validator->getChecks();
        $this->assertSame('fail', $checks[0]['status']);
        $this->assertStringContainsString('vue', $checks[0]['detail']);
    }

    public function testDeprecatedDepsPassVue3(): void
    {
        $this->writeJson($this->tmpDir . '/composer.json', ['require' => []]);
        $this->writeJson($this->tmpDir . '/package.json', [
            'dependencies' => ['vue' => '^3.0'],
        ]);
        $this->validator->checkDeprecatedDependencies($this->tmpDir);
        $checks = $this->validator->getChecks();
        $this->assertSame('pass', $checks[0]['status']);
    }

    public function testDeprecatedDepsFailJest(): void
    {
        $this->writeJson($this->tmpDir . '/composer.json', ['require' => []]);
        $this->writeJson($this->tmpDir . '/package.json', [
            'devDependencies' => ['jest' => '^26.0'],
        ]);
        $this->validator->checkDeprecatedDependencies($this->tmpDir);
        $checks = $this->validator->getChecks();
        $this->assertSame('fail', $checks[0]['status']);
        $this->assertStringContainsString('jest', $checks[0]['detail']);
    }

    public function testDeprecatedDepsFailVueCli(): void
    {
        $this->writeJson($this->tmpDir . '/composer.json', ['require' => []]);
        $this->writeJson($this->tmpDir . '/package.json', [
            'devDependencies' => ['@vue/cli-service' => '^4.0'],
        ]);
        $this->validator->checkDeprecatedDependencies($this->tmpDir);
        $checks = $this->validator->getChecks();
        $this->assertSame('fail', $checks[0]['status']);
        $this->assertStringContainsString('@vue/cli-service', $checks[0]['detail']);
    }

    public function testDeprecatedDepsFailNoComposerJson(): void
    {
        $this->validator->checkDeprecatedDependencies($this->tmpDir);
        $checks = $this->validator->getChecks();
        $this->assertSame('fail', $checks[0]['status']);
    }

    public function testDeprecatedDepsNoPackageJsonStillChecksComposer(): void
    {
        $this->writeJson($this->tmpDir . '/composer.json', [
            'require' => ['php' => '>=8.5'],
        ]);
        // No package.json — should still pass (only composer deps checked)
        $this->validator->checkDeprecatedDependencies($this->tmpDir);
        $checks = $this->validator->getChecks();
        $this->assertSame('pass', $checks[0]['status']);
    }

    public function testDeprecatedDepsMultipleIssues(): void
    {
        $this->writeJson($this->tmpDir . '/composer.json', [
            'require-dev' => ['silex/silex' => '^2.2'],
        ]);
        $this->writeJson($this->tmpDir . '/package.json', [
            'dependencies' => ['vue' => '^2.6'],
            'devDependencies' => ['jest' => '^26.0', '@vue/cli-service' => '^4.0'],
        ]);
        $this->validator->checkDeprecatedDependencies($this->tmpDir);
        $checks = $this->validator->getChecks();
        $this->assertSame('fail', $checks[0]['status']);
        $detail = $checks[0]['detail'];
        $this->assertStringContainsString('silex/silex', $detail);
        $this->assertStringContainsString('vue', $detail);
        $this->assertStringContainsString('jest', $detail);
        $this->assertStringContainsString('@vue/cli-service', $detail);
    }

    // ── Deprecated PHP API patterns ──

    public function testPhpPatternsPassCleanCode(): void
    {
        file_put_contents($this->tmpDir . '/app.php', '<?php echo "hello";');
        $this->validator->checkDeprecatedPhpPatterns($this->tmpDir);
        $checks = $this->validator->getChecks();
        $this->assertSame('pass', $checks[0]['status']);
    }

    public function testPhpPatternsFailSilexApplication(): void
    {
        file_put_contents($this->tmpDir . '/app.php', '<?php use Silex\Application; $app = new Application();');
        $this->validator->checkDeprecatedPhpPatterns($this->tmpDir);
        $checks = $this->validator->getChecks();
        $this->assertSame('fail', $checks[0]['status']);
        $this->assertStringContainsString('Silex', $checks[0]['detail']);
    }

    public function testPhpPatternsFailSilexProvider(): void
    {
        file_put_contents($this->tmpDir . '/app.php', '<?php use Silex\Provider\TwigServiceProvider;');
        $this->validator->checkDeprecatedPhpPatterns($this->tmpDir);
        $checks = $this->validator->getChecks();
        $this->assertSame('fail', $checks[0]['status']);
    }

    public function testPhpPatternsFailRouteRegistration(): void
    {
        file_put_contents($this->tmpDir . '/app.php', '<?php $app->get("/", function() {});');
        $this->validator->checkDeprecatedPhpPatterns($this->tmpDir);
        $checks = $this->validator->getChecks();
        $this->assertSame('fail', $checks[0]['status']);
    }

    public function testPhpPatternsIgnoreComments(): void
    {
        $code = <<<'PHP'
<?php
// This is a comment mentioning Silex\Application
/* Another comment with Silex\Provider */
echo "clean code";
PHP;
        file_put_contents($this->tmpDir . '/app.php', $code);
        $this->validator->checkDeprecatedPhpPatterns($this->tmpDir);
        $checks = $this->validator->getChecks();
        $this->assertSame('pass', $checks[0]['status']);
    }

    public function testPhpPatternsIgnoreStrings(): void
    {
        $code = <<<'PHP'
<?php
$msg = "Silex\Application is deprecated";
$msg2 = 'Use Silex\Provider for legacy';
echo $msg;
PHP;
        file_put_contents($this->tmpDir . '/app.php', $code);
        $this->validator->checkDeprecatedPhpPatterns($this->tmpDir);
        $checks = $this->validator->getChecks();
        $this->assertSame('pass', $checks[0]['status']);
    }

    public function testPhpPatternsNoPhpFiles(): void
    {
        file_put_contents($this->tmpDir . '/readme.txt', 'Silex\Application');
        $this->validator->checkDeprecatedPhpPatterns($this->tmpDir);
        $checks = $this->validator->getChecks();
        $this->assertSame('pass', $checks[0]['status']);
    }

    public function testPhpPatternsEmptyDirectory(): void
    {
        $this->validator->checkDeprecatedPhpPatterns($this->tmpDir);
        $checks = $this->validator->getChecks();
        $this->assertSame('pass', $checks[0]['status']);
    }

    // ── Deprecated JS/Vue API patterns ──

    public function testJsPatternsPassCleanCode(): void
    {
        file_put_contents($this->tmpDir . '/app.js', 'import { createApp } from "vue"; createApp({}).mount("#app");');
        $this->validator->checkDeprecatedJsPatterns($this->tmpDir);
        $checks = $this->validator->getChecks();
        $this->assertSame('pass', $checks[0]['status']);
    }

    public function testJsPatternsFailNewVue(): void
    {
        file_put_contents($this->tmpDir . '/app.js', 'const vm = new Vue({ el: "#app" });');
        $this->validator->checkDeprecatedJsPatterns($this->tmpDir);
        $checks = $this->validator->getChecks();
        $this->assertSame('fail', $checks[0]['status']);
        $this->assertStringContainsString('Vue 2 instantiation', $checks[0]['detail']);
    }

    public function testJsPatternsFailVuePrototype(): void
    {
        file_put_contents($this->tmpDir . '/app.js', 'Vue.prototype.$http = axios;');
        $this->validator->checkDeprecatedJsPatterns($this->tmpDir);
        $checks = $this->validator->getChecks();
        $this->assertSame('fail', $checks[0]['status']);
        $this->assertStringContainsString('Vue 2 prototype', $checks[0]['detail']);
    }

    public function testJsPatternsFailJestFn(): void
    {
        file_put_contents($this->tmpDir . '/test.js', 'const mock = jest.fn();');
        $this->validator->checkDeprecatedJsPatterns($this->tmpDir);
        $checks = $this->validator->getChecks();
        $this->assertSame('fail', $checks[0]['status']);
        $this->assertStringContainsString('Jest API', $checks[0]['detail']);
    }

    public function testJsPatternsFailJestSpyOn(): void
    {
        file_put_contents($this->tmpDir . '/test.js', 'jest.spyOn(obj, "method");');
        $this->validator->checkDeprecatedJsPatterns($this->tmpDir);
        $checks = $this->validator->getChecks();
        $this->assertSame('fail', $checks[0]['status']);
    }

    public function testJsPatternsFailJestMock(): void
    {
        file_put_contents($this->tmpDir . '/test.js', 'jest.mock("./module");');
        $this->validator->checkDeprecatedJsPatterns($this->tmpDir);
        $checks = $this->validator->getChecks();
        $this->assertSame('fail', $checks[0]['status']);
    }

    public function testJsPatternsFailProductionTip(): void
    {
        file_put_contents($this->tmpDir . '/app.js', 'Vue.config.productionTip = false;');
        $this->validator->checkDeprecatedJsPatterns($this->tmpDir);
        $checks = $this->validator->getChecks();
        $this->assertSame('fail', $checks[0]['status']);
    }

    public function testJsPatternsIgnoreComments(): void
    {
        $code = <<<'JS'
// const vm = new Vue({});
/* Vue.prototype.$http = axios; */
import { createApp } from 'vue';
JS;
        file_put_contents($this->tmpDir . '/app.js', $code);
        $this->validator->checkDeprecatedJsPatterns($this->tmpDir);
        $checks = $this->validator->getChecks();
        $this->assertSame('pass', $checks[0]['status']);
    }

    public function testJsPatternsIgnoreStrings(): void
    {
        $code = <<<'JS'
const msg = "new Vue({ el: '#app' })";
const msg2 = 'Vue.prototype.$http';
console.log(msg);
JS;
        file_put_contents($this->tmpDir . '/app.js', $code);
        $this->validator->checkDeprecatedJsPatterns($this->tmpDir);
        $checks = $this->validator->getChecks();
        $this->assertSame('pass', $checks[0]['status']);
    }

    public function testJsPatternsVueFiles(): void
    {
        file_put_contents($this->tmpDir . '/App.vue', '<script>const vm = new Vue({});</script>');
        $this->validator->checkDeprecatedJsPatterns($this->tmpDir);
        $checks = $this->validator->getChecks();
        $this->assertSame('fail', $checks[0]['status']);
    }

    public function testJsPatternsMultipleIssuesInOneFile(): void
    {
        $code = <<<'JS'
const vm = new Vue({ el: '#app' });
Vue.prototype.$http = axios;
const mock = jest.fn();
JS;
        file_put_contents($this->tmpDir . '/app.js', $code);
        $this->validator->checkDeprecatedJsPatterns($this->tmpDir);
        $checks = $this->validator->getChecks();
        $this->assertSame('fail', $checks[0]['status']);
        // Should report multiple patterns
        $detail = $checks[0]['detail'];
        $this->assertStringContainsString('Vue 2 instantiation', $detail);
        $this->assertStringContainsString('Vue 2 prototype', $detail);
        $this->assertStringContainsString('Jest API', $detail);
    }

    // ── Removed files ──

    public function testRemovedFilesPassCleanProject(): void
    {
        $this->validator->checkRemovedFiles($this->tmpDir);
        $checks = $this->validator->getChecks();
        $this->assertSame('pass', $checks[0]['status']);
    }

    public function testRemovedFilesFailVueConfig(): void
    {
        touch($this->tmpDir . '/vue.config.js');
        $this->validator->checkRemovedFiles($this->tmpDir);
        $checks = $this->validator->getChecks();
        $this->assertSame('fail', $checks[0]['status']);
        $this->assertStringContainsString('vue.config.js', $checks[0]['detail']);
    }

    public function testRemovedFilesFailBabelConfig(): void
    {
        touch($this->tmpDir . '/babel.config.js');
        $this->validator->checkRemovedFiles($this->tmpDir);
        $checks = $this->validator->getChecks();
        $this->assertSame('fail', $checks[0]['status']);
        $this->assertStringContainsString('babel.config.js', $checks[0]['detail']);
    }

    public function testRemovedFilesFailJestConfig(): void
    {
        touch($this->tmpDir . '/jest.config.js');
        $this->validator->checkRemovedFiles($this->tmpDir);
        $checks = $this->validator->getChecks();
        $this->assertSame('fail', $checks[0]['status']);
        $this->assertStringContainsString('jest.config.js', $checks[0]['detail']);
    }

    public function testRemovedFilesFailBuildDir(): void
    {
        mkdir($this->tmpDir . '/build');
        $this->validator->checkRemovedFiles($this->tmpDir);
        $checks = $this->validator->getChecks();
        $this->assertSame('fail', $checks[0]['status']);
        $this->assertStringContainsString('build', $checks[0]['detail']);
    }

    public function testRemovedFilesFailMultiple(): void
    {
        touch($this->tmpDir . '/vue.config.js');
        touch($this->tmpDir . '/babel.config.js');
        mkdir($this->tmpDir . '/build');
        $this->validator->checkRemovedFiles($this->tmpDir);
        $checks = $this->validator->getChecks();
        $this->assertSame('fail', $checks[0]['status']);
        $detail = $checks[0]['detail'];
        $this->assertStringContainsString('vue.config.js', $detail);
        $this->assertStringContainsString('babel.config.js', $detail);
        $this->assertStringContainsString('build', $detail);
    }

    public function testRemovedFilesCustomList(): void
    {
        touch($this->tmpDir . '/old-file.txt');
        $this->validator->checkRemovedFiles($this->tmpDir, ['old-file.txt', 'nonexistent.txt']);
        $checks = $this->validator->getChecks();
        $this->assertSame('fail', $checks[0]['status']);
        $this->assertStringContainsString('old-file.txt', $checks[0]['detail']);
        $this->assertStringNotContainsString('nonexistent.txt', $checks[0]['detail']);
    }

    // ── validate() (full integration) ──

    public function testValidateCleanProject(): void
    {
        $this->writeJson($this->tmpDir . '/composer.json', [
            'require' => ['php' => '>=8.5'],
        ]);
        $report = $this->validator->validate($this->tmpDir);
        $this->assertSame(5, $report['summary']['total']);
        $this->assertSame(5, $report['summary']['passed']);
        $this->assertSame(0, $report['summary']['failed']);
    }

    public function testValidateOldProject(): void
    {
        $this->writeJson($this->tmpDir . '/composer.json', [
            'require' => ['php' => '>=7.0'],
            'require-dev' => ['silex/silex' => '^2.2'],
        ]);
        $this->writeJson($this->tmpDir . '/package.json', [
            'dependencies' => ['vue' => '^2.6'],
            'devDependencies' => ['jest' => '^26.0'],
        ]);
        file_put_contents($this->tmpDir . '/index.php', '<?php use Silex\Application; $app = new Application();');
        file_put_contents($this->tmpDir . '/app.js', 'const vm = new Vue({});');
        touch($this->tmpDir . '/vue.config.js');
        mkdir($this->tmpDir . '/build');

        $report = $this->validator->validate($this->tmpDir);
        $this->assertSame(5, $report['summary']['total']);
        $this->assertSame(0, $report['summary']['passed']);
        $this->assertSame(5, $report['summary']['failed']);
    }

    public function testValidatePartiallyMigrated(): void
    {
        // PHP side migrated, frontend not yet
        $this->writeJson($this->tmpDir . '/composer.json', [
            'require' => ['php' => '>=8.5', 'oasis/http' => '^3.0'],
        ]);
        $this->writeJson($this->tmpDir . '/package.json', [
            'dependencies' => ['vue' => '^2.6'],
            'devDependencies' => ['jest' => '^26.0'],
        ]);
        file_put_contents($this->tmpDir . '/app.js', 'const vm = new Vue({});');
        touch($this->tmpDir . '/vue.config.js');

        $report = $this->validator->validate($this->tmpDir);
        $this->assertSame(5, $report['summary']['total']);
        // PHP constraint pass, deprecated deps fail, PHP patterns pass, JS patterns fail, removed files fail
        $this->assertSame(2, $report['summary']['passed']);
        $this->assertSame(3, $report['summary']['failed']);
    }

    // ── stripCommentsAndStrings() ──

    public function testStripPhpCommentsAndStrings(): void
    {
        $code = <<<'PHP'
<?php
// single line comment with Silex\Application
/* multi-line
   comment with Silex\Provider */
$msg = "string with Silex\Application";
$msg2 = 'another string';
use Actual\Code;
PHP;
        $stripped = $this->validator->stripCommentsAndStrings($code, 'test.php');
        $this->assertStringNotContainsString('single line comment', $stripped);
        $this->assertStringNotContainsString('multi-line', $stripped);
        $this->assertStringNotContainsString('string with', $stripped);
        $this->assertStringContainsString('Actual\\Code', $stripped);
    }

    public function testStripJsCommentsAndStrings(): void
    {
        $code = <<<'JS'
// comment with new Vue
/* block comment with Vue.prototype */
const msg = "new Vue({})";
const msg2 = 'Vue.prototype';
const actual = createApp({});
JS;
        $stripped = $this->validator->stripCommentsAndStrings($code, 'test.js');
        $this->assertStringNotContainsString('comment with new Vue', $stripped);
        $this->assertStringNotContainsString('block comment', $stripped);
        $this->assertStringContainsString('createApp', $stripped);
    }

    // ── findSourceFiles() ──

    public function testFindSourceFilesRecursive(): void
    {
        mkdir($this->tmpDir . '/sub', 0755, true);
        touch($this->tmpDir . '/a.php');
        touch($this->tmpDir . '/sub/b.php');
        touch($this->tmpDir . '/c.txt');

        $files = $this->validator->findSourceFiles($this->tmpDir, ['php']);
        $this->assertCount(2, $files);
    }

    public function testFindSourceFilesSkipsVendor(): void
    {
        mkdir($this->tmpDir . '/vendor/pkg', 0755, true);
        touch($this->tmpDir . '/vendor/pkg/lib.php');
        touch($this->tmpDir . '/app.php');

        $files = $this->validator->findSourceFiles($this->tmpDir, ['php']);
        $this->assertCount(1, $files);
    }

    public function testFindSourceFilesSkipsNodeModules(): void
    {
        mkdir($this->tmpDir . '/node_modules/pkg', 0755, true);
        touch($this->tmpDir . '/node_modules/pkg/index.js');
        touch($this->tmpDir . '/app.js');

        $files = $this->validator->findSourceFiles($this->tmpDir, ['js']);
        $this->assertCount(1, $files);
    }

    public function testFindSourceFilesNonexistentDir(): void
    {
        $files = $this->validator->findSourceFiles('/nonexistent/path', ['php']);
        $this->assertSame([], $files);
    }

    // ── Unicode / special content ──

    public function testPhpPatternsWithUnicodeContent(): void
    {
        $code = <<<'PHP'
<?php
// 中文注释：使用 Silex\Application
$msg = "中文字符串";
echo $msg;
PHP;
        file_put_contents($this->tmpDir . '/app.php', $code);
        $this->validator->checkDeprecatedPhpPatterns($this->tmpDir);
        $checks = $this->validator->getChecks();
        // Silex\Application is in a comment, should pass
        $this->assertSame('pass', $checks[0]['status']);
    }

    public function testJsPatternsWithUnicodeContent(): void
    {
        $code = <<<'JS'
// 中文注释：new Vue({})
const msg = "中文字符串";
import { createApp } from 'vue';
JS;
        file_put_contents($this->tmpDir . '/app.js', $code);
        $this->validator->checkDeprecatedJsPatterns($this->tmpDir);
        $checks = $this->validator->getChecks();
        $this->assertSame('pass', $checks[0]['status']);
    }

    // ── PBT: Feature: release-4.0 ──

    /**
     * Feature: release-4.0, Property 13: dependency/version validation
     *
     * For any composer.json (with arbitrary PHP version constraint and
     * arbitrary dependency sets), the migration validator should correctly
     * identify: (a) whether the PHP version constraint satisfies >= 8.5;
     * (b) whether deprecated dependencies exist.
     *
     * **Validates: Requirements 15.1, 15.2**
     */
    public function testPbtDependencyVersionValidation(): void
    {
        // PHP constraint generators
        $phpConstraintGen = Generators::oneOf(
            // Constraints that satisfy >= 8.5
            Generators::elements(['>=8.5', '>=9.0', '^8.5', '^9.0', '~8.5', '>8.4', '8.5', '9.0']),
            // Constraints that do NOT satisfy >= 8.5
            Generators::elements(['>=7.0', '>=8.0', '^7.4', '^8.0', '~7.4', '~8.0', '>7.0', '7.4', '8.0']),
        );

        // Whether to include silex/silex
        $hasSilexGen = Generators::bool();

        $this->limitTo(50);
        $this->forAll($phpConstraintGen, $hasSilexGen)->then(
            function (string $phpConstraint, bool $hasSilex): void {
                $tmpDir = $this->tmpDir . '/pbt13-' . uniqid();
                mkdir($tmpDir, 0755, true);

                $composerData = [
                    'require' => ['php' => $phpConstraint],
                ];
                if ($hasSilex) {
                    $composerData['require-dev'] = ['silex/silex' => '^2.2'];
                }
                $this->writeJson($tmpDir . '/composer.json', $composerData);

                $validator = new MigrationValidator();
                $report = $validator->validate($tmpDir);

                // Find the PHP version constraint check
                $phpCheck = null;
                $depCheck = null;
                foreach ($report['checks'] as $check) {
                    if ($check['name'] === 'PHP version constraint') {
                        $phpCheck = $check;
                    }
                    if ($check['name'] === 'Deprecated dependencies') {
                        $depCheck = $check;
                    }
                }

                $this->assertNotNull($phpCheck, 'PHP version constraint check should exist');
                $this->assertNotNull($depCheck, 'Deprecated dependencies check should exist');

                // Verify PHP constraint check matches our expectation
                $expectedPhpPass = $validator->phpConstraintSatisfies85($phpConstraint);
                $this->assertSame(
                    $expectedPhpPass ? 'pass' : 'fail',
                    $phpCheck['status'],
                    "PHP constraint '$phpConstraint' should " . ($expectedPhpPass ? 'pass' : 'fail'),
                );

                // Verify deprecated dependency check
                if ($hasSilex) {
                    $this->assertSame('fail', $depCheck['status'], 'Should detect silex/silex as deprecated');
                    $this->assertStringContainsString('silex/silex', $depCheck['detail']);
                } else {
                    $this->assertSame('pass', $depCheck['status'], 'No deprecated deps should pass');
                }

                $this->removeDir($tmpDir);
            },
        );
    }

    /**
     * Feature: release-4.0, Property 14: deprecated pattern detection
     *
     * For any source file content, the migration validator should correctly
     * detect deprecated API patterns in actual code and NOT produce false
     * positives for patterns in comments and strings.
     *
     * **Validates: Requirements 15.3, 15.4**
     */
    public function testPbtDeprecatedPatternDetection(): void
    {
        // Generate random "safe" identifiers for variable names
        $identGen = Generators::elements([
            'foo', 'bar', 'baz', 'qux', 'abc', 'xyz', 'tmp', 'val', 'obj', 'res',
        ]);

        // Whether to inject a deprecated pattern in code vs comment/string
        $locationGen = Generators::elements(['code', 'comment', 'string']);

        // Which deprecated pattern to test
        $patternGen = Generators::elements([
            ['php', 'new Vue(', 'js'],  // JS pattern in PHP file should not trigger
            ['js', 'new Vue({', 'js'],
            ['js', 'Vue.prototype', 'js'],
            ['js', 'jest.fn(', 'js'],
            ['php', 'Silex\\Application', 'php'],
        ]);

        $this->limitTo(50);
        $this->forAll($identGen, $locationGen, $patternGen)->then(
            function (string $ident, string $location, array $patternInfo): void {
                [$fileType, $pattern, $detectionType] = $patternInfo;
                $tmpDir = $this->tmpDir . '/pbt14-' . uniqid();
                mkdir($tmpDir, 0755, true);

                if ($fileType === 'php') {
                    $content = $this->buildPhpFileWithPattern($ident, $pattern, $location);
                    file_put_contents($tmpDir . '/test.php', $content);
                } else {
                    $content = $this->buildJsFileWithPattern($ident, $pattern, $location);
                    file_put_contents($tmpDir . '/test.js', $content);
                }

                $validator = new MigrationValidator();

                if ($detectionType === 'php') {
                    $validator->checkDeprecatedPhpPatterns($tmpDir);
                } else {
                    $validator->checkDeprecatedJsPatterns($tmpDir);
                }

                $checks = $validator->getChecks();
                $this->assertNotEmpty($checks);

                if ($location === 'code' && $fileType === $detectionType) {
                    // Pattern in actual code of the correct file type → should detect
                    $this->assertSame(
                        'fail',
                        $checks[0]['status'],
                        "Should detect '$pattern' in $fileType code",
                    );
                } else {
                    // Pattern in comment/string or wrong file type → should NOT detect
                    $this->assertSame(
                        'pass',
                        $checks[0]['status'],
                        "Should NOT detect '$pattern' in $location of $fileType file (detection: $detectionType)",
                    );
                }

                $this->removeDir($tmpDir);
            },
        );
    }

    /**
     * Feature: release-4.0, Property 15: removed files detection
     *
     * For any project directory (with an arbitrary subset of removed files
     * present), the migration validator should correctly identify which
     * removed files still exist.
     *
     * **Validates: Requirements 15.5**
     */
    public function testPbtRemovedFilesDetection(): void
    {
        $removedFiles = ['vue.config.js', 'babel.config.js', 'jest.config.js', 'build'];

        // Generate a random subset of files to create
        $subsetGen = Generators::subset($removedFiles);

        $this->limitTo(50);
        $this->forAll($subsetGen)->then(function (array $presentFiles): void {
            $removedFiles = ['vue.config.js', 'babel.config.js', 'jest.config.js', 'build'];
            $tmpDir = $this->tmpDir . '/pbt15-' . uniqid();
            mkdir($tmpDir, 0755, true);

            // Create the selected subset of files
            foreach ($presentFiles as $file) {
                $path = $tmpDir . '/' . $file;
                if ($file === 'build') {
                    mkdir($path, 0755, true);
                } else {
                    touch($path);
                }
            }

            $validator = new MigrationValidator();
            $validator->checkRemovedFiles($tmpDir);
            $checks = $validator->getChecks();
            $this->assertNotEmpty($checks);

            if ($presentFiles === []) {
                // No removed files present → should pass
                $this->assertSame('pass', $checks[0]['status']);
            } else {
                // Some removed files present → should fail
                $this->assertSame('fail', $checks[0]['status']);
                // Each present file should be mentioned in the detail
                foreach ($presentFiles as $file) {
                    $this->assertStringContainsString(
                        $file,
                        $checks[0]['detail'],
                        "Should report '$file' as present",
                    );
                }
                // Files NOT present should NOT be mentioned
                $absentFiles = array_diff($removedFiles, $presentFiles);
                foreach ($absentFiles as $file) {
                    $this->assertStringNotContainsString(
                        $file,
                        $checks[0]['detail'],
                        "Should NOT report '$file' as present",
                    );
                }
            }

            $this->removeDir($tmpDir);
        });
    }

    // ── PBT helper methods ──

    private function buildPhpFileWithPattern(string $ident, string $pattern, string $location): string
    {
        $dollar = '$';
        return match ($location) {
            'code'    => "<?php\nuse " . $pattern . ";\n" . $dollar . $ident . " = 1;\n",
            'comment' => "<?php\n// " . $pattern . "\n" . $dollar . $ident . " = 1;\n",
            'string'  => "<?php\n" . $dollar . $ident . ' = "' . $pattern . "\";\n",
        };
    }

    private function buildJsFileWithPattern(string $ident, string $pattern, string $location): string
    {
        return match ($location) {
            'code'    => 'const ' . $ident . ' = ' . $pattern . ");\n",
            'comment' => '// ' . $pattern . "\nconst " . $ident . " = 1;\n",
            'string'  => 'const ' . $ident . ' = "' . $pattern . "\";\n",
        };
    }
}
