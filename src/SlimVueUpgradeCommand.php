<?php
/**
 * Created by PhpStorm.
 * User: minhao
 * Date: 18/09/2017
 * Time: 2:08 PM
 */

namespace Oasis\SlimVue;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

class SlimVueUpgradeCommand extends Command
{
    public function __construct($name)
    {
        parent::__construct($name);
    }
    
    protected function configure()
    {
        parent::configure();
        $this->setDescription('Initialize the slimvue directory, and symlink needed files/directories');
        $this->addArgument('project-dir', InputArgument::REQUIRED, "directory of existing project");
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $projectDir = $input->getArgument('project-dir');
        
        $cwd              = \getcwd();
        $fs               = new Filesystem();
        $targetSlimvueDir = $fs->isAbsolutePath($projectDir) ? $fs->makePathRelative(
            $projectDir,
            $cwd
        ) : $projectDir;
        
        // get old package info, which will be set back to package.json after upgrade
        $packageJsonFile = $targetSlimvueDir . "/package.json";
        $content         = \file_get_contents($packageJsonFile);
        $packageJson     = \json_decode($content, true);
        $oldName         = $packageJson['name'];
        $oldVersion      = $packageJson['version'];
        $oldDep          = $packageJson['dependencies'];
        $oldDevDep       = $packageJson['devDependencies'];
        
        $output->writeln(
            \sprintf(
                "Will update slimvue directory at: <info>%s</info>",
                $targetSlimvueDir
            )
        );
        $fs->mirror(SlimVueInitializeCommand::SLIMVUE_DIR, $targetSlimvueDir);
        $webpackDevConfigFile = $targetSlimvueDir . "/build/webpack.dev.conf.js";
        $content              = \file_get_contents($webpackDevConfigFile);
        $content              = \str_replace(
            '/slimvue-template/dist/',
            '/' . $projectDir . '/dist/',
            $content
        );
        
        // restore package.json name&version
        \file_put_contents($webpackDevConfigFile, $content);
        $packageJsonFile                = $targetSlimvueDir . "/package.json";
        $content                        = \file_get_contents($packageJsonFile);
        $packageJson                    = \json_decode($content, true);
        $packageJson['name']            = $oldName;
        $packageJson['version']         = $oldVersion;
        $packageJson['dependencies']    = \array_merge($oldDep, $packageJson['dependencies']);
        $packageJson['devDependencies'] = \array_merge($oldDevDep, $packageJson['devDependencies']);
        \file_put_contents($packageJsonFile, \json_encode($content, \JSON_PRETTY_PRINT));
        
        \usleep(200 * 1000);
        $output->writeln("Project upgraded, remember to check your git working-tree for detailed changes.");
    }
    
}
