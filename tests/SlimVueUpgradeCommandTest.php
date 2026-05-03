<?php

namespace Oasis\SlimVue\Tests;

use Oasis\SlimVue\SlimVueInitializeCommand;
use Oasis\SlimVue\SlimVueUpgradeCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class SlimVueUpgradeCommandTest extends TestCase
{
    /** @var string */
    private $originalCwd;

    /** @var string */
    private $tmpDir;

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
        $app->add($cmd);
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
        $this->assertFileExists($projDir . '/vue.config.js');
        $this->assertDirectoryExists($projDir . '/src');
        $this->assertDirectoryExists($projDir . '/build');
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

    public function testUpgradeHandlesMissingDependenciesKeyGracefully(): void
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

        $pkg = \json_decode(\file_get_contents($projDir . '/package.json'), true);
        $this->assertSame('bare-project', $pkg['name']);
        $this->assertSame('0.0.1', $pkg['version']);
        // dependencies should exist (empty or from template, depending on mirror behavior)
        $this->assertArrayHasKey('dependencies', $pkg);
        $this->assertArrayHasKey('devDependencies', $pkg);
    }

    public function testUpgradeHandlesMissingNameKeyGracefully(): void
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

        $pkg = \json_decode(\file_get_contents($projDir . '/package.json'), true);
        // fallback to 'slimvue-template' via ?? operator
        $this->assertSame('slimvue-template', $pkg['name']);
    }
}
