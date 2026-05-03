<?php

namespace Oasis\SlimVue;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

class SlimVueUpgradeCommand extends Command
{
    private const OBSOLETE_FILES = [
        'build',
        'vue.config.js',
        'babel.config.js',
        'jest.config.js',
        '.eslintrc.js',
    ];

    private const REQUIRED_PACKAGE_FIELDS = [
        'name',
        'version',
        'dependencies',
        'devDependencies',
    ];

    public function __construct(string $name)
    {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        parent::configure();
        $this->setDescription('Upgrade an existing slimvue project to the latest template');
        $this->addArgument('project-dir', InputArgument::REQUIRED, 'directory of existing project');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $projectDir = $input->getArgument('project-dir');

        $cwd              = \getcwd();
        $fs               = new Filesystem();
        $targetSlimvueDir = $fs->isAbsolutePath($projectDir) ? $fs->makePathRelative(
            $projectDir,
            $cwd,
        ) : $projectDir;

        // Read and validate existing package.json
        $packageJsonFile = $targetSlimvueDir . '/package.json';
        $content         = \file_get_contents($packageJsonFile);
        $packageJson     = \json_decode($content, true);

        // Validate required fields (Gatekeep Q1 decision)
        foreach (self::REQUIRED_PACKAGE_FIELDS as $field) {
            if (!\array_key_exists($field, $packageJson)) {
                $output->writeln(
                    "<error>Missing required field '$field' in package.json. Please fix manually and retry.</error>"
                );

                return Command::FAILURE;
            }
        }

        $oldName   = $packageJson['name'];
        $oldVersion = $packageJson['version'];
        $oldDep    = $packageJson['dependencies'];
        $oldDevDep = $packageJson['devDependencies'];

        $output->writeln(
            \sprintf(
                'Will update slimvue directory at: <info>%s</info>',
                $targetSlimvueDir,
            )
        );
        $fs->mirror(
            SlimVueInitializeCommand::SLIMVUE_DIR,
            $targetSlimvueDir,
            SlimVueInitializeCommand::templateIterator(),
        );

        // Remove obsolete files from target directory
        foreach (self::OBSOLETE_FILES as $obsoleteFile) {
            $path = $targetSlimvueDir . '/' . $obsoleteFile;
            if ($fs->exists($path)) {
                $fs->remove($path);
            }
        }

        // Restore package.json with preserved fields
        $packageJsonFile                = $targetSlimvueDir . '/package.json';
        $content                        = \file_get_contents($packageJsonFile);
        $packageJson                    = \json_decode($content, true);
        $packageJson['name']            = $oldName;
        $packageJson['version']         = $oldVersion;
        $packageJson['dependencies']    = \array_merge($oldDep, $packageJson['dependencies'] ?? []);
        $packageJson['devDependencies'] = \array_merge($oldDevDep, $packageJson['devDependencies'] ?? []);
        \file_put_contents($packageJsonFile, \json_encode($packageJson, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES));

        $this->sleep(200 * 1000);
        $output->writeln('Project upgraded, remember to check your git working-tree for detailed changes.');

        return Command::SUCCESS;
    }

    protected function sleep(int $microseconds): void
    {
        \usleep($microseconds);
    }
}
