<?php

namespace Oasis\SlimVue;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class SlimVueInitializeCommand extends Command
{
    public const SLIMVUE_DIR = __DIR__ . '/../slimvue-template';

    public static function templateIterator(): Finder
    {
        return Finder::create()
            ->in(self::SLIMVUE_DIR)
            ->exclude(['node_modules', 'coverage', 'build'])
            ->notName(['vue.config.js', 'babel.config.js', 'jest.config.js'])
            ->ignoreDotFiles(false);
    }

    protected function sleep(int $microseconds): void
    {
        \usleep($microseconds);
    }

    public function __construct(string $name)
    {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        parent::configure();
        $this->setDescription('Initialize the slimvue directory, and symlink needed files/directories');
        $this->addArgument('project-name', InputArgument::OPTIONAL, 'name of project');
        $this->addOption(
            'scope',
            null,
            InputOption::VALUE_REQUIRED,
            'scope of project',
        );
        $this->addOption(
            'directory',
            'd',
            InputOption::VALUE_REQUIRED,
            'directory to install slimvue framework',
        );
        $this->addOption(
            'twig',
            't',
            InputOption::VALUE_REQUIRED,
            'twig templates base directory',
            './templates',
        );
        $this->addOption(
            'service-dir',
            null,
            InputOption::VALUE_REQUIRED,
            'directory containing service files; a twig-bridge service file will be created here',
            './config',
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
            . \PHP_EOL,
            './web',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $projectName = $input->getArgument('project-name');
        while (!\preg_match('/^[a-z_][a-z0-9_-]*$/', (string) $projectName)) {
            $q = new Question(
                'Please provide a project name, which may conatin only lowercase letters, numbers and hyphen: '
            );
            /** @var QuestionHelper $helper */
            $helper      = $this->getHelper('question');
            $projectName = $helper->ask($input, $output, $q);
        }
        $projectDir          = $input->getOption('directory') ?: "./slimvue-$projectName";
        $scope               = $input->getOption('scope');
        $fullProjectName     = ($scope ? "@$scope/" : '') . $projectName;
        $twigTemplateBaseDir = $input->getOption('twig');
        $serviceDir          = $input->getOption('service-dir');
        $webDir              = $input->getOption('web-dir');

        $cwd              = \getcwd();
        $fs               = new Filesystem();
        $targetSlimvueDir = $fs->isAbsolutePath($projectDir) ? $fs->makePathRelative(
            $projectDir,
            $cwd,
        ) : $projectDir;
        $relativeDistDir  = $targetSlimvueDir . '/dist';
        $absoluteDistDir  = $cwd . '/' . $targetSlimvueDir . '/dist';
        $twigToDir        = $fs->isAbsolutePath($twigTemplateBaseDir) ?
            $fs->makePathRelative($twigTemplateBaseDir, $cwd)
            : $twigTemplateBaseDir . '/slimvue';
        $twigAsDir        = $fs->makePathRelative("{$cwd}{$relativeDistDir}/pages", dirname("{$cwd}{$twigToDir}"));
        $serviceFile      = $serviceDir . '/slimvue.services.yml';

        $output->writeln(
            \sprintf(
                'Will create slimvue directory at: <info>%s</info>',
                $targetSlimvueDir,
            )
        );
        $fs->mirror(self::SLIMVUE_DIR, $targetSlimvueDir, self::templateIterator());

        $packageJsonFile        = $targetSlimvueDir . '/package.json';
        $content                = \file_get_contents($packageJsonFile);
        $packageJson            = \json_decode($content, true);
        $packageJson['name']    = $fullProjectName;
        $packageJson['version'] = '0.1.0';
        \file_put_contents($packageJsonFile, \json_encode($packageJson, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES));

        $this->sleep(200 * 1000);
        $output->writeln(
            \sprintf(
                'Will link twig directory to: <info>%s</info>, as <info>%s</info>',
                $twigToDir,
                $twigAsDir,
            )
        );
        $fs->symlink($twigAsDir, $twigToDir);

        $this->sleep(200 * 1000);
        $output->writeln("Will link resource directories to: <info>$webDir</info>");
        foreach (['fonts', 'js', 'img', 'css', 'static'] as $subdir) {
            $fs->symlink($absoluteDistDir . "/$subdir", $webDir . "/$subdir");
        }

        $this->sleep(200 * 1000);
        $output->writeln("Will create twig service file at: <info>$serviceFile</info>");
        $serviceYaml = <<<YAML
services:
    slimvue.bridge:
        class: Oasis\SlimVue\TwigBridgeInfo
        arguments:
            - [] # constants

YAML;
        $fs->dumpFile($serviceFile, $serviceYaml);

        $this->sleep(200 * 1000);
        $output->writeln('');
        $this->sleep(500 * 1000);
        $output->writeln('<info>Slim Vue framework has been initialized for your project.</info> ');
        $this->sleep(500 * 1000);
        $output->writeln('');
        $output->writeln('<info>To use the twig template, render your page using the following statement:</info>');
        $renderSample = <<<PHP
    \$kernel->render(
        "slimvue/\$yourControllerName.twig",
        [
            "title" => \$yourPageTitle,
            "bridge" => \$theBridgeObject, <comment>// It is recommended to inject the bridge object into global twig vars</comment>
        ]
    );
    
PHP;
        $output->writeln('');
        $output->writeln($renderSample);
        $output->writeln('');
        $output->writeln('<info>To import the bridge object, edit your services.yml:</info>');
        $serviceYamlImports = <<<YAML
    imports:
        - {resource: "slimvue.services.yml"} <comment># add this line</comment>
    
YAML;
        $output->writeln('');
        $output->writeln($serviceYamlImports);
        $output->writeln('');
        $output->writeln('<info>To add the bridge object into global twig vars, edit your services.yml:</info>');
        $output->writeln('');
        $globalVarEdit = <<<YAML
    app:
        http:
            twig:
                globals:
                    bridge: "@slimvue.bridge" <comment># add this line</comment>
YAML;
        $output->writeln($globalVarEdit);
        $output->writeln('');
        $output->writeln("<info>To build your slimvue front-end, switch to $targetSlimvueDir, and run:</info>");
        $output->writeln('');
        $output->writeln(
            "\tnpm install               <comment>(RUN ONCE, install node packages accordingly)</comment>"
        );
        $output->writeln('');
        $output->writeln(
            "\tnpm run dev               <comment>(use Vite dev server)</comment>"
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
        $output->writeln('');

        return Command::SUCCESS;
    }
}
