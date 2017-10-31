<?php
/**
 * Created by PhpStorm.
 * User: minhao
 * Date: 18/09/2017
 * Time: 2:08 PM
 */

namespace Oasis\SlimVue;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
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
        $output->writeln(
            \sprintf(
                "Will update slimvue directory at: <info>%s</info>",
                $targetSlimvueDir
            )
        );
        $fs->mirror(SlimVueInitializeCommand::SLIMVUE_DIR, $targetSlimvueDir);
        \usleep(200 * 1000);
        $output->writeln("Project upgraded, remember to check your git working-tree for detailed changes.");
    }
    
}
