<?php declare(strict_types=1);

namespace Parable\Framework\Commands;

use Parable\Console\Command;
use Parable\Framework\Application;
use Parable\Framework\Path;

class InstallCommand extends Command
{
    public function __construct(
        protected Path $path
    ) {
        $this->setName('install');
        $this->setDescription('Install Parable.');
    }

    public function run(): void
    {
        $this->output->writelns(
            sprintf('Welcome to the installation of Parable %s!', Application::VERSION),
            '',
            'This will install Parable in a basic form. If you\'ve previously run this command,',
            'this may overwrite existing files if you choose the same public directory.',
        );

        if (!$this->askUserToContinue()) {
            $this->output->writeln('<info>You chose not to continue.</info>');
            return;
        }

        $composerJson = file_get_contents(BASEDIR . '/composer.json');
        $composerArray = json_decode($composerJson, true, 512, JSON_THROW_ON_ERROR);

        $rootNamespaceAndSourceDir = $composerArray['autoload']['psr-4'] ?? null;

        $upgrading = false;

        if ($rootNamespaceAndSourceDir === null) {
            $this->output->writeln(
                '<yellow>No root namespace or source directory found in composer.json, assuming new install.</yellow>'
            );
        } else {
            $this->output->writeln(
                '<yellow>Root namespace and source directory found in composer.json, assuming upgrade.</yellow>'
            );

            $existingRootNamespace = array_keys($rootNamespaceAndSourceDir)[0];
            $existingSourceDir = $rootNamespaceAndSourceDir[$existingRootNamespace];

            $existingRootNamespace = trim($existingRootNamespace, '\\');

            $this->output->writeln(sprintf('  detected root namespace:     <cyan>%s</cyan>', $existingRootNamespace));
            $this->output->writeln(sprintf('  detected source namespace:   <cyan>%s</cyan>', $existingSourceDir));

            $upgrading = $this->askUserToContinue(
                'Do you want to use these? (No will revert to assuming fresh install)'
            );

            if ($upgrading) {
                $this->output->writeln(sprintf(
                    'Making <cyan>%s</cyan> and <cyan>%s</cyan> the default values.',
                    $existingRootNamespace,
                    $existingSourceDir
                ));
            } else {
                $this->output->writeln('Not using detected root namespace and source directory.');
            }

            $this->output->newline();
        }

        $namespace = $this->askUserAQuestionWithDefault(
            'What is your project\'s root namespace?',
            $existingRootNamespace ?? 'Project'
        );
        $sourceDir = $this->askUserAQuestionWithDefault(
            'What source directory do you want to use?',
            $existingSourceDir ?? 'src'
        );
        $publicDir = $this->askUserAQuestionWithDefault(
            'What public directory do you want to use?',
            'public'
        );

        $this->output->newline();

        $this->output->writeln('<yellow>You have chosen:</yellow>');

        $this->output->writeln(sprintf(
            '  root namespace:     <cyan>%s</cyan> (classes such as \\' . $namespace . '\\Controller)',
            $namespace
        ));
        $this->output->writeln(sprintf(
            '  source directory:   <cyan>%s</cyan> (your project files will be loaded from here)',
            $sourceDir
        ));
        $this->output->writeln(sprintf(
            '  public directory:   <cyan>%s</cyan> (index.php will be situated here and is the place for your css/js)',
            $publicDir
        ));

        $sourceDirFull = $this->path->getPath($sourceDir);
        $publicDirFull = $this->path->getPath($publicDir);

        $this->output->newline();

        $this->output->writeln('<yellow>The following directories will now be created (if non-existent):</yellow>');
        $this->output->writeln('  ' . $sourceDirFull);
        $this->output->writeln('  ' . $publicDirFull);

        if (!$this->askUserToContinue()) {
            $this->output->writeln('<info>You chose not to continue.</info>');
            return;
        }

        if (!$this->createDirectory($sourceDirFull)) {
            return;
        }

        if (!$this->createDirectory($publicDirFull)) {
            return;
        }

        $this->output->write(sprintf('Updating composer.json with autoload for %s... ', $namespace));

        $composerJson = file_get_contents(BASEDIR . '/composer.json');
        $composerArray = json_decode($composerJson, true, 512, JSON_THROW_ON_ERROR);

        if (!array_key_exists('autoload', $composerArray)) {
            $composerArray['autoload'] = [];
        }

        if (!array_key_exists('psr-4', $composerArray['autoload'])) {
            $composerArray['autoload']['psr-4'] = [];
        }

        $composerArray['autoload']['psr-4'][$namespace . '\\'] = $sourceDir;

        if (!array_key_exists('files', $composerArray['autoload'])) {
            $composerArray['autoload']['files'] = [];
        }

        if (!in_array('parable_init.php', $composerArray['autoload']['files'], true)) {
            $composerArray['autoload']['files'][] = 'parable_init.php';
        }

        $composerJson = json_encode($composerArray, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);
        $composerJson = str_replace('\/', DS, $composerJson);
        file_put_contents(BASEDIR . '/composer.json', $composerJson);

        $this->output->writeln('<success>[OK]</success>');

        if ($this->askUserToContinue(
            'Do you want to install the bootstrap files (index.php, parable_init.php)? (say no if you\'ve made changes to these)',
            true
        )) {
            if (!$this->copyTemplateFile('parable_init.php', null, $namespace, $sourceDir, $publicDir)) {
                return;
            }
            if (!$this->copyTemplateFile('index.php', $publicDir, $namespace, $sourceDir, $publicDir)) {
                return;
            }
        }

        if ($upgrading === false
            && $this->askUserToContinue(
                'Do you want to install the example files (Boot.php, ExamplePlugin.php, welcome.phtml)? (say no if you\'re upgrading)',
                true
            )
        ) {
                if (!$this->copyTemplateFile('Boot.php', $sourceDir, $namespace, $sourceDir, $publicDir)) {
                    return;
                }
                if (!$this->copyTemplateFile('ExamplePlugin.php', $sourceDir, $namespace, $sourceDir, $publicDir)) {
                    return;
                }
                if (!$this->copyTemplateFile('welcome.phtml', $sourceDir, $namespace, $sourceDir, $publicDir)) {
                    return;
                }
            }

        if ($this->askUserToContinue('Do you want to install the (Apache 2.4+) .htaccess files? (say no if you\'ve made changes to these)')) {
            if (!$this->copyTemplateFile('.htaccess_root', null, $namespace, $sourceDir, $publicDir)) {
                return;
            }
            rename(BASEDIR . DS . '.htaccess_root', BASEDIR . DS . '.htaccess');

            if (!$this->copyTemplateFile('.htaccess_public', $publicDir, $namespace, $sourceDir, $publicDir)) {
                return;
            }
            rename($publicDir . DS . '.htaccess_public', $publicDir . DS . '.htaccess');

            $this->output->newline();
        }

        $this->output->writeln('Parable ' . Application::VERSION . ' has been installed!');
        $this->output->newline();
        $this->output->writeln('You can start adding your code to ' . $sourceDir . '.');
        $this->output->newline();
        $this->output->writeln('Before Parable will work properly, you will need to `composer dump-autoload`.');
    }

    protected function askUserToContinue(string $question = 'Do you want to continue?', bool $default = false): bool
    {
        $this->output->newline();

        if ($default === true) {
            $defaultString = 'Y/n';
        } else {
            $defaultString = 'y/N';
        }

        $this->output->write('<yellow>' . $question . ' [' . $defaultString . '] </yellow>');

        $continue = $this->input->getYesNo($default);

        $this->output->newline();

        return $continue;
    }

    protected function askUserAQuestionWithDefault(string $question, string $default = null): ?string
    {
        $this->output->write($question);
        if ($default !== null) {
            $this->output->write(' (default: ' . $default . ')');
        }
        $this->output->write(' ');

        $answer = $this->input->get();

        if (empty($answer)) {
            $answer = $default;
        }

        return $answer;
    }

    protected function createDirectory(string $path): bool
    {
        if (!is_dir($path)) {
            $this->output->write('Creating ' . $path . '... ');

            if (!mkdir($path) && !is_dir($path)) {
                $this->output->writeln('<error>[ERROR]</error>');
                return false;
            }

            $this->output->writeln('<success>[OK]</success>');
        } else {
            $this->output->writeln('Directory ' . $path . ' already exists <info>[SKIP]</info> ');
        }

        return true;
    }

    protected function copyTemplateFile(
        string $filename,
        ?string $targetDirectory,
        string $namespace,
        string $sourceDir,
        string $publicDir
    ): bool {
        $outputFilename = $filename;

        // Ew. But also it works. We make choices. There are consequences. I can live with them.
        if (strpos($outputFilename, '.htaccess')) {
            $outputFilename = '.htaccess';
        }

        $fullTargetDir = BASEDIR . DS . ($targetDirectory ? $targetDirectory . DS : null) . $outputFilename;

        $this->output->write('Writing ' . $filename . ' -> ' . $fullTargetDir . '... ');

        $code = $this->loadAndPopulateTemplate($filename, $namespace, $sourceDir, $publicDir);

        if ($code !== null && @file_put_contents(BASEDIR . DS . $targetDirectory . DS . $filename, $code)) {
            $this->output->writeln('<success>[OK]</success>');
            return true;
        }

        $this->output->writeln('<error>[ERROR]</error>');
        return false;
    }

    protected function loadAndPopulateTemplate(
        string $filename,
        string $namespace,
        string $sourceDir,
        string $publicDir
    ): ?string {
        $structurePath = __DIR__ . DS . '..' . DS . '..' . DS . 'structure';

        $contents = @file_get_contents($structurePath . DS . $filename . '_template');

        if ($contents === false) {
            $this->output->writeln('<error>Could not read file!</error>');
            return null;
        }

        return str_replace(
            [
                '###ROOT_NAMESPACE###',
                '###SOURCE_DIRECTORY###',
                '###PUBLIC_DIRECTORY###'
            ],
            [
                $namespace,
                $sourceDir,
                $publicDir
            ],
            $contents
        );
    }
}
