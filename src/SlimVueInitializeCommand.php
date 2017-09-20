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

class SlimVueInitializeCommand extends Command
{
    const SLIMVUE_DIR = __DIR__ . "/../slimvue_framework";
    
    public function __construct($name)
    {
        parent::__construct($name);
    }
    
    protected function configure()
    {
        parent::configure();
        $this->setDescription('Initialize the slimvue directory, and symlink needed files/directories');
        $this->addArgument('project-name', InputArgument::OPTIONAL, "name of project");
        $this->addOption(
            'directory',
            'd',
            InputOption::VALUE_REQUIRED,
            'directory to install slimvue framework',
            './slimvue'
        );
        $this->addOption(
            'assets',
            'a',
            InputOption::VALUE_REQUIRED,
            'assets directory of project, will be linked to slimvue directory',
            './assets'
        );
        $this->addOption(
            'twig',
            't',
            InputOption::VALUE_REQUIRED,
            'twig templates base directory',
            './templates'
        );
        $this->addOption(
            'service-dir',
            null,
            InputOption::VALUE_REQUIRED,
            'directory containing service files; a twig-bridge service file will be created here',
            './config'
        );
        $this->addOption(
            'web-dir',
            'w',
            InputOption::VALUE_REQUIRED,
            'web directory into which project specific files will be linked;'
            . \PHP_EOL
            . ' all project files will be put under a sub-directory named by project name;'
            . \PHP_EOL
            . '<comment>e.g.</comment> project named <info>test</info> may have the following links created under web-dir: <comment>test/js, test/img, test/assets</comment>'
            . \PHP_EOL
            ,
            '/data/htdocs'
        );
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $name = $input->getArgument('project-name');
        while (!\preg_match($pattern = '/^[a-z_][a-z0-9_-]*$/', $name)) {
            $q = new Question("Please provide a project name:");
            /** @var QuestionHelper $helper */
            $helper = $this->getHelper('question');
            $name   = $helper->ask($input, $output, $q);
        }
        $dir        = $input->getOption('directory');
        $assetsOrig = $input->getOption('assets');
        $twigTarget = $input->getOption('twig');
        $serviceDir = $input->getOption('service-dir');
        $webDir     = $input->getOption('web-dir');
        
        $fs               = new Filesystem();
        $targetSlimvueDir = $fs->isAbsolutePath($dir) ? $dir : (\getcwd() . "/" . $dir);
        $originAssetsDir  = $fs->isAbsolutePath($assetsOrig) ? $assetsOrig : (\getcwd() . "/" . $assetsOrig);
        $twigTargetDir    = $fs->isAbsolutePath($twigTarget) ? $twigTarget : (\getcwd() . "/" . $twigTarget);
        $twigTargetDir    .= "/slimvue";
        $serviceFile      = $fs->isAbsolutePath($serviceDir) ? $serviceDir : (\getcwd() . "/" . $serviceDir);
        $serviceFile      .= "/slimvue.services.yml";
        $webDir           = $fs->isAbsolutePath($webDir) ? $webDir : (\getcwd() . "/" . $webDir);
        $webDir           .= "/$name";
        $output->writeln("Will create slimvue directory at: <info>$targetSlimvueDir</info>");
        $fs->mirror(self::SLIMVUE_DIR, $targetSlimvueDir);
        \usleep(200 * 1000);
        $output->writeln("Will link assets directory from: <info>$originAssetsDir</info>");
        $fs->symlink($originAssetsDir, $targetSlimvueDir . "/src/assets");
        \usleep(200 * 1000);
        $output->writeln("Will link twig directory to: <info>$twigTargetDir</info>");
        $fs->symlink($targetSlimvueDir . "/dist/twigs", $twigTargetDir);
        \usleep(200 * 1000);
        $output->writeln("Will link resource directories to: <info>$webDir</info>");
        $fs->symlink($targetSlimvueDir . "/dist/js", $webDir . "/js");
        $fs->symlink($targetSlimvueDir . "/dist/assets", $webDir . "/assets");
        $fs->symlink($targetSlimvueDir . "/dist/img", $webDir . "/img");
        \usleep(200 * 1000);
        $output->writeln("Will create twig service file at: <info>$serviceFile</info>");
        $serviceYaml = <<<YAML
services:
    slimvue.bridge:
        class: Oasis\SlimVue\TwigBridgeInfo
        arguments:
            - [] # constants

YAML;
        $fs->dumpFile($serviceFile, $serviceYaml);
        \usleep(200 * 1000);
        
        $output->writeln("");
        \usleep(500 * 1000);
        $output->writeln("<info>Slim Vue framework has been initialized for your project.</info> ");
        \usleep(500 * 1000);
        $output->writeln("");
        $output->writeln("<info>To use the twig template, render your page using the following statement:</info>");
        $renderSample = <<<PHP
    \$kernel->render(
        "slimvue/slimvue-\$yourControllerName.twig",
        [
            "title" => \$yourPageTitle,
            "bridge" => \$theBridgeObject, <comment>// It is recommended to inject the bridge object into global twig vars</comment>
        ]
    );
    
PHP;
        $output->writeln("");
        $output->writeln($renderSample);
        $output->writeln("");
        $output->writeln("<info>To import the bridge object, edit your services.yml:</info>");
        $serviceYamlImports = <<<YAML
    imports:
        - {resource: "slimvue.services.yml"} <comment># add this line</comment>
    
YAML;
        $output->writeln("");
        $output->writeln($serviceYamlImports);
        $output->writeln("");
        $output->writeln("<info>To add the bridge object into global twig vars, edit your services.yml:</info>");
        $output->writeln("");
        $globalVarEdit = <<<YAML
    app:
        http:
            twig:
                globals:
                    bridge: "@slimvue.bridge" <comment># add this line</comment>
YAML;
        $output->writeln($globalVarEdit);
        $output->writeln("");
        $output->writeln("<info>To build your slimvue front-end, switch to $targetSlimvueDir, and run:</info>");
        $output->writeln("");
        $output->writeln("\tnpm run make-htmlonly     <comment>(for html only debugging)</comment>");
        $output->writeln("\tnpm run make              <comment>(for routed debugging)</comment>");
        $output->writeln("\tnpm run make-production   <comment>(for routed and compressed production build)</comment>");
        $output->writeln("");
    }
    
}
