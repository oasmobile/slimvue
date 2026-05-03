<?php

namespace Oasis\SlimVue\Tests;

use Eris\Generators;
use Eris\TestTrait;
use Oasis\SlimVue\SlimVueInitializeCommand;
use Oasis\SlimVue\SlimVueUpgradeCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class SlimVueUpgradeCommandTest extends TestCase
{
    use TestTrait;
    private string $originalCwd;
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->originalCwd = \getcwd();
        $this->tmpDir = \sys_get_temp_dir() . '/slimvue_upgrade_test_' . \uniqid();
        \mkdir($this->tmpDir, 0755, true);
        \chdir($this->tmpDir);
    }

    protected function tearDown(): void
    {
        \chdir($this->originalCwd);
        $this->removeDir($this->tmpDir);
    }

    private function removeDir(string $dir): void
    {
        if (!\is_dir($dir)) {
            return;
        }
        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($items as $item) {
            if ($item->isLink() || $item->isFile()) {
                @\unlink($item->getPathname());
            } elseif ($item->isDir()) {
                @\rmdir($item->getPathname());
            }
        }
        @\rmdir($dir);
    }

    /**
     * Create a fake project directory with a package.json to simulate an existing project.
     */
    private function createFakeProject(string $dir, array $packageOverrides = []): void
    {
        \mkdir($dir, 0755, true);
        $pkg = \array_merge([
            'name'            => 'my-existing-project',
            'version'         => '1.2.3',
            'dependencies'    => ['axios' => '^0.21'],
            'devDependencies' => ['mocha' => '^8.0'],
        ], $packageOverrides);
        \file_put_contents($dir . '/package.json', \json_encode($pkg, \JSON_PRETTY_PRINT));
    }

    private function createUpgradeTester(): CommandTester
    {
        $app = new Application('slimvue', '1.4');
        $cmd = new class('upgrade') extends SlimVueUpgradeCommand {
            protected function sleep(int $microseconds): void {}
        };
        $app->addCommand($cmd);
        $command = $app->find('upgrade');
        return new CommandTester($command);
    }

    // ── configure() ──

    public function testCommandHasCorrectName(): void
    {
        $cmd = new SlimVueUpgradeCommand('upgrade');
        $this->assertSame('upgrade', $cmd->getName());
    }

    public function testCommandHasDescription(): void
    {
        $cmd = new SlimVueUpgradeCommand('upgrade');
        $this->assertNotEmpty($cmd->getDescription());
    }

    public function testCommandHasProjectDirArgument(): void
    {
        $cmd = new SlimVueUpgradeCommand('upgrade');
        $def = $cmd->getDefinition();
        $this->assertTrue($def->hasArgument('project-dir'));
        $this->assertTrue($def->getArgument('project-dir')->isRequired());
    }

    // ── execute() ──

    public function testUpgradePreservesProjectName(): void
    {
        $projDir = $this->tmpDir . '/myproj';
        $this->createFakeProject($projDir, ['name' => '@scope/myproj']);

        $tester = $this->createUpgradeTester();
        $tester->execute(['project-dir' => $projDir]);

        $pkg = \json_decode(\file_get_contents($projDir . '/package.json'), true);
        $this->assertSame('@scope/myproj', $pkg['name']);
    }

    public function testUpgradePreservesProjectVersion(): void
    {
        $projDir = $this->tmpDir . '/verproj';
        $this->createFakeProject($projDir, ['version' => '2.5.0']);

        $tester = $this->createUpgradeTester();
        $tester->execute(['project-dir' => $projDir]);

        $pkg = \json_decode(\file_get_contents($projDir . '/package.json'), true);
        $this->assertSame('2.5.0', $pkg['version']);
    }

    public function testUpgradePreservesExistingDependencies(): void
    {
        $projDir = $this->tmpDir . '/depproj';
        $this->createFakeProject($projDir, [
            'dependencies' => ['axios' => '^0.21', 'lodash' => '^4.0'],
        ]);

        $tester = $this->createUpgradeTester();
        $tester->execute(['project-dir' => $projDir]);

        $pkg = \json_decode(\file_get_contents($projDir . '/package.json'), true);
        // mirror does not overwrite existing package.json, so deps are preserved as-is
        $this->assertArrayHasKey('axios', $pkg['dependencies']);
        $this->assertArrayHasKey('lodash', $pkg['dependencies']);
    }

    public function testUpgradePreservesExistingDevDependencies(): void
    {
        $projDir = $this->tmpDir . '/devdepproj';
        $this->createFakeProject($projDir, [
            'devDependencies' => ['mocha' => '^8.0'],
        ]);

        $tester = $this->createUpgradeTester();
        $tester->execute(['project-dir' => $projDir]);

        $pkg = \json_decode(\file_get_contents($projDir . '/package.json'), true);
        $this->assertArrayHasKey('mocha', $pkg['devDependencies']);
    }

    public function testUpgradeKeepsExistingDepVersionsWhenMirrorDoesNotOverwrite(): void
    {
        $projDir = $this->tmpDir . '/overrideproj';
        $this->createFakeProject($projDir, [
            'dependencies' => ['vue' => '^2.5.0'],
        ]);

        $tester = $this->createUpgradeTester();
        $tester->execute(['project-dir' => $projDir]);

        $pkg = \json_decode(\file_get_contents($projDir . '/package.json'), true);
        // mirror does not overwrite existing package.json, so old version is kept
        $this->assertSame('^2.5.0', $pkg['dependencies']['vue']);
    }

    public function testUpgradeMirrorsTemplateFiles(): void
    {
        $projDir = $this->tmpDir . '/mirrorproj';
        $this->createFakeProject($projDir);

        $tester = $this->createUpgradeTester();
        $tester->execute(['project-dir' => $projDir]);

        $this->assertFileExists($projDir . '/slimvue.js');
        $this->assertDirectoryExists($projDir . '/src');
        // Obsolete files should be removed after upgrade
        $this->assertFileDoesNotExist($projDir . '/vue.config.js');
        $this->assertFileDoesNotExist($projDir . '/babel.config.js');
        $this->assertFileDoesNotExist($projDir . '/jest.config.js');
        $this->assertFileDoesNotExist($projDir . '/.eslintrc.js');
        $this->assertDirectoryDoesNotExist($projDir . '/build');
    }

    public function testUpgradeOutputContainsUpgradedMessage(): void
    {
        $projDir = $this->tmpDir . '/outproj';
        $this->createFakeProject($projDir);

        $tester = $this->createUpgradeTester();
        $tester->execute(['project-dir' => $projDir]);

        $output = $tester->getDisplay();
        $this->assertStringContainsString('upgraded', \strtolower($output));
    }

    public function testUpgradeFailsWhenDependenciesKeyMissing(): void
    {
        $projDir = $this->tmpDir . '/nodepproj';
        \mkdir($projDir, 0755, true);
        // package.json without dependencies/devDependencies keys
        \file_put_contents($projDir . '/package.json', \json_encode([
            'name'    => 'bare-project',
            'version' => '0.0.1',
        ], \JSON_PRETTY_PRINT));

        $tester = $this->createUpgradeTester();
        $tester->execute(['project-dir' => $projDir]);

        $this->assertSame(1, $tester->getStatusCode());
        $output = $tester->getDisplay();
        $this->assertStringContainsString('Missing required field', $output);
        $this->assertStringContainsString('dependencies', $output);
    }

    public function testUpgradeFailsWhenNameKeyMissing(): void
    {
        $projDir = $this->tmpDir . '/nonameproj';
        \mkdir($projDir, 0755, true);
        \file_put_contents($projDir . '/package.json', \json_encode([
            'version'         => '0.0.1',
            'dependencies'    => ['vue' => '^2.6.11'],
            'devDependencies' => [],
        ], \JSON_PRETTY_PRINT));

        $tester = $this->createUpgradeTester();
        $tester->execute(['project-dir' => $projDir]);

        $this->assertSame(1, $tester->getStatusCode());
        $output = $tester->getDisplay();
        $this->assertStringContainsString('Missing required field', $output);
        $this->assertStringContainsString('name', $output);
    }

    public function testUpgradeFailsWhenVersionKeyMissing(): void
    {
        $projDir = $this->tmpDir . '/noverproj';
        \mkdir($projDir, 0755, true);
        \file_put_contents($projDir . '/package.json', \json_encode([
            'name'            => 'test-project',
            'dependencies'    => ['vue' => '^3'],
            'devDependencies' => [],
        ], \JSON_PRETTY_PRINT));

        $tester = $this->createUpgradeTester();
        $tester->execute(['project-dir' => $projDir]);

        $this->assertSame(1, $tester->getStatusCode());
        $output = $tester->getDisplay();
        $this->assertStringContainsString('Missing required field', $output);
        $this->assertStringContainsString('version', $output);
    }

    public function testUpgradeFailsWhenDevDependenciesKeyMissing(): void
    {
        $projDir = $this->tmpDir . '/nodevdepproj';
        \mkdir($projDir, 0755, true);
        \file_put_contents($projDir . '/package.json', \json_encode([
            'name'         => 'test-project',
            'version'      => '1.0.0',
            'dependencies' => ['vue' => '^3'],
        ], \JSON_PRETTY_PRINT));

        $tester = $this->createUpgradeTester();
        $tester->execute(['project-dir' => $projDir]);

        $this->assertSame(1, $tester->getStatusCode());
        $output = $tester->getDisplay();
        $this->assertStringContainsString('Missing required field', $output);
        $this->assertStringContainsString('devDependencies', $output);
    }

    // ── PBT: Feature: release-4.0 ──

    /**
     * Feature: release-4.0, Property 5: name+version preservation
     *
     * For any existing project (with name, version, dependencies, devDependencies),
     * after upgrade, package.json name and version should be preserved.
     *
     * **Validates: Requirements 4.5**
     */
    public function testPbtNameVersionPreservation(): void
    {
        // Generate random project names
        $nameGen = Generators::map(
            function (array $parts): string {
                return '@scope/' . $parts[0] . \implode('', $parts[1]);
            },
            Generators::tuple(
                Generators::elements(\array_merge(\range('a', 'z'), ['_'])),
                Generators::vector(
                    4,
                    Generators::elements(\array_merge(\range('a', 'z'), \range('0', '9'), ['_', '-'])),
                ),
            ),
        );

        // Generate random semver-like versions
        $versionGen = Generators::map(
            fn(array $parts): string => \implode('.', $parts),
            Generators::vector(3, Generators::choose(0, 99)),
        );

        $this->limitTo(10);
        $this->forAll($nameGen, $versionGen)->then(function (string $name, string $version): void {
            $projDir = $this->tmpDir . '/pbt-upgrade-' . \uniqid();
            $this->createFakeProject($projDir, [
                'name'    => $name,
                'version' => $version,
            ]);

            $tester = $this->createUpgradeTester();
            $tester->execute(['project-dir' => $projDir]);

            $this->assertSame(0, $tester->getStatusCode());
            $pkg = \json_decode(\file_get_contents($projDir . '/package.json'), true);
            $this->assertSame($name, $pkg['name']);
            $this->assertSame($version, $pkg['version']);
        });
    }

    /**
     * Feature: release-4.0, Property 7: removes obsolete files
     *
     * For any project containing obsolete files (build/, vue.config.js,
     * babel.config.js, jest.config.js, .eslintrc.js), after upgrade,
     * those files should be removed.
     *
     * **Validates: Requirements 12.4**
     */
    public function testPbtRemovesObsoleteFiles(): void
    {
        $obsoleteFiles = ['vue.config.js', 'babel.config.js', 'jest.config.js', '.eslintrc.js'];

        // Generate random subsets of obsolete files to place in the project
        $subsetGen = Generators::subset($obsoleteFiles);

        $this->limitTo(10);
        $this->forAll($subsetGen)->then(function (array $filesToPlace) use ($obsoleteFiles): void {
            $projDir = $this->tmpDir . '/pbt-obsolete-' . \uniqid();
            $this->createFakeProject($projDir);

            // Place the selected obsolete files
            foreach ($filesToPlace as $file) {
                \file_put_contents($projDir . '/' . $file, '// obsolete');
            }
            // Always also create build/ directory
            if (!\is_dir($projDir . '/build')) {
                \mkdir($projDir . '/build', 0755, true);
                \file_put_contents($projDir . '/build/dummy.js', '// obsolete');
            }

            $tester = $this->createUpgradeTester();
            $tester->execute(['project-dir' => $projDir]);

            $this->assertSame(0, $tester->getStatusCode());

            // All obsolete files should be removed after upgrade
            foreach ($obsoleteFiles as $file) {
                $this->assertFileDoesNotExist($projDir . '/' . $file, "Obsolete file '$file' should be removed after upgrade");
            }
            $this->assertDirectoryDoesNotExist($projDir . '/build', "Obsolete directory 'build/' should be removed after upgrade");
        });
    }
}
