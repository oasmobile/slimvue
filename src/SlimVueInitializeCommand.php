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
    const SLIMVUE_DIR = __DIR__ . "/../slimvue-template";
    
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
        $dir                 = $input->getOption('directory');
        $twigTemplateBaseDir = $input->getOption('twig');
        $serviceDir          = $input->getOption('service-dir');
        $webDir              = $input->getOption('web-dir');
        
        $cwd              = \getcwd();
        $fs               = new Filesystem();
        $targetSlimvueDir = $fs->isAbsolutePath($dir) ? $fs->makePathRelative(
            $dir,
            $cwd
        ) : $dir;
        $relativeDistDir  = $targetSlimvueDir . "/dist";
        $absoluteDistDir  = $cwd . "/" . $targetSlimvueDir . "/dist";
        $twigToDir        = $fs->isAbsolutePath($twigTemplateBaseDir) ?
            $fs->makePathRelative($twigTemplateBaseDir, $cwd)
            : $twigTemplateBaseDir . "/slimvue";
        $twigAsDir        = $fs->makePathRelative($relativeDistDir . "/twigs", \dirname($twigToDir));
        $serviceFile      = $serviceDir . "/slimvue.services.yml";
        $webDir           = $webDir . "/$name";
        $output->writeln(
            \sprintf(
                "Will create slimvue directory at: <info>%s</info>",
                $targetSlimvueDir
            )
        );
        $fs->mirror(self::SLIMVUE_DIR, $targetSlimvueDir);
        \usleep(200 * 1000);
        $output->writeln(
            \sprintf(
                "Will link twig directory to: <info>%s</info>, as <info>%s</info>",
                $twigToDir,
                $twigAsDir
            )
        );
        $fs->symlink(
            $twigAsDir,
            $twigToDir
        );
        \usleep(200 * 1000);
        $output->writeln("Will link resource directories to: <info>$webDir</info>");
        foreach (['js', 'assets', 'static'] as $subdir) {
            $fs->symlink($absoluteDistDir . "/$subdir", $webDir . "/$subdir");
        }
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
        $output->writeln(
            "\tnpm run dev               <comment>(use webpack dev server)</comment>"
        );
        $output->writeln(
            "\tnpm run build             <comment>(build for debug environment)</comment>"
        );
        $output->writeln(
            "\tnpm run make-production   <comment>(build for production environment, with optional compression feature)</comment>"
        );
        $output->writeln("");
    }
    
}