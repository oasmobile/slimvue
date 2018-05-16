<?php
/**
 * Created by PhpStorm.
 * User: minhao
 * Date: 18/09/2017
 * Time: 2:08 PM
 */

namespace Oasis\SlimVue;

use Oasis\Mlib\FlysystemWrappers\ExtendedFilesystem;
use Oasis\Mlib\FlysystemWrappers\ExtendedLocal;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\SplFileInfo;

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
            null
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
        $projectName = $input->getArgument('project-name');
        while (!\preg_match($pattern = '/^[a-z_][a-z0-9_-]*$/', $projectName)) {
            $q = new Question(
                "Please provide a project name, which may conatin only lowercase letters, numbers and hyphen: "
            );
            /** @var QuestionHelper $helper */
            $helper      = $this->getHelper('question');
            $projectName = $helper->ask($input, $output, $q);
        }
        $projectDir          = $input->getOption('directory') ? : "./slimvue-$projectName";
        $twigTemplateBaseDir = $input->getOption('twig');
        $serviceDir          = $input->getOption('service-dir');
        $webDir              = $input->getOption('web-dir');
        
        $cwd              = \getcwd();
        $fs               = new Filesystem();
        $targetSlimvueDir = $fs->isAbsolutePath($projectDir) ? $fs->makePathRelative(
            $projectDir,
            $cwd
        ) : $projectDir;
        $relativeDistDir  = $targetSlimvueDir . "/dist";
        $absoluteDistDir  = $cwd . "/" . $targetSlimvueDir . "/dist";
        $twigToDir        = $fs->isAbsolutePath($twigTemplateBaseDir) ?
            $fs->makePathRelative($twigTemplateBaseDir, $cwd)
            : $twigTemplateBaseDir . "/slimvue";
        $twigAsDir        = $fs->makePathRelative($relativeDistDir . "/twigs", \dirname($twigToDir));
        $serviceFile      = $serviceDir . "/slimvue.services.yml";
        $webDir           = $webDir . "/$projectName";
        $output->writeln(
            \sprintf(
                "Will create slimvue directory at: <info>%s</info>",
                $targetSlimvueDir
            )
        );
        $fs->mirror(self::SLIMVUE_DIR, $targetSlimvueDir);
        $output->writeln(\sprintf("Will customize for this project by changing some generated file content"));
        $webpackDevConfigFile = $targetSlimvueDir . "/build/webpack.dev.conf.js";
        $content              = \file_get_contents($webpackDevConfigFile);
        $content              = \str_replace(
            '/slimvue-template/dist/',
            '/slimvue-' . $projectName . '/dist/',
            $content
        );
        \file_put_contents($webpackDevConfigFile, $content);
        $packageJsonFile        = $targetSlimvueDir . "/package.json";
        $content                = \file_get_contents($packageJsonFile);
        $packageJson            = \json_decode($content, true);
        $packageJson['name']    = "slimvue-$projectName";
        $packageJson['version'] = '0.1.0';
        \file_put_contents($packageJsonFile, \json_encode($packageJson, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES));
        $efs    = new ExtendedFilesystem(new ExtendedLocal($targetSlimvueDir));
        $finder = $efs->getFinder();
        $finder->path('src/')->files()->name('/\.(js|vue)$/');
        /** @var SplFileInfo $splFileInfo */
        foreach ($finder as $splFileInfo) {
            $path    = $splFileInfo->getRealPath();
            $content = \file_get_contents($path);
            $content = \preg_replace('#([\'"])src/#', '$1/' . $projectName, $content);
            \file_put_contents($path, $content);
        }
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
        foreach (['htmls', 'js', 'assets', 'static'] as $subdir) {
            $fs->symlink($absoluteDistDir . "/$subdir", $webDir . "/$subdir");
            $fs->symlink($absoluteDistDir . "/$subdir", $webDir . "/slimvue-$projectName/dist/$subdir");
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
        "slimvue/pages/\$yourControllerName.twig",
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
            "\tnpm install               <comment>(RUN ONCE, install node packages accordingly)</comment>"
        );
        $output->writeln("");
        $output->writeln(
            "\tnpm run dev               <comment>(use webpack dev server)</comment>"
        );
        $output->writeln(
            "\tnpm run start             <comment>(alias to dev)</comment>"
        );
        $output->writeln(
            "\tnpm run build             <comment>(build for debug environment)</comment>"
        );
        $output->writeln(
            "\tnpm run watch             <comment>(build for debug environment, and watch for file changes)</comment>"
        );
        $output->writeln(
            "\tnpm run release           <comment>(build for production/release environment)</comment>"
        );
        $output->writeln(
            "\tnpm run prepare-library   <comment>(prepare current project to work as a library, which can then be published to npm repo)</comment>"
        );
        $output->writeln(
            "\tnpm run library           <comment>(alias to prepare-library)</comment>"
        );
        $output->writeln("");
    }
    
}
