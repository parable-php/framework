<?php declare(strict_types=1);

namespace Parable\Framework\Commands;

use Parable\Console\Command;
use Parable\Framework\Application;
use Parable\Framework\Path;

class Install extends Command
{
    /**
     * @var Path
     */
    protected $path;
    /**
     * @var string
     */
    protected $name = 'install';

    /**
     * @var string
     */
    protected $description = 'Install Parable.';

    public function __construct(Path $path)
    {
        $this->path = $path;
    }

    public function run(): void
    {
        $this->output->writelns([
            'Welcome to the installation of Parable ' . Application::VERSION . '!',
            '',
            'This will install Parable in a basic form. If you\'ve previously run this command,',
            'this may overwrite existing files if you choose the same public directory.',
        ]);

        if (!$this->askUserToContinue()) {
            $this->output->writeln('<info>You chose not to continue.</info>');
            return;
        }

        $composerJson = file_get_contents(BASEDIR . '/composer.json');
        $composerArray = json_decode($composerJson, true);

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

            $this->output->writeln('  detected root namespace:     <cyan>' . $existingRootNamespace . '</cyan>');
            $this->output->writeln('  detected source directory:   <cyan>' . $existingSourceDir . '</cyan>');

            $upgrading = $useExisting = $this->askUserToContinue('Do you want to use these? (No will revert to assuming fresh install)');

            if ($upgrading) {
                $this->output->writeln('Making <cyan>' . $existingRootNamespace . '</cyan> and <cyan>' . $existingSourceDir . '</cyan> the default values.');
            } else {
                $this->output->writeln('Not using detected root namespace and source directory.');
            }

            $this->output->newline();
        }

        $namespace = $this->askUserAQuestionWithDefault(
            'What is your project\'s root namespace?',
            $upgrading ? $existingRootNamespace : 'Project'
        );
        $sourceDir = $this->askUserAQuestionWithDefault(
            'What source directory do you want to use?',
            $upgrading ? $existingSourceDir : 'src'
        );
        $publicDir = $this->askUserAQuestionWithDefault(
            'What public directory do you want to use?',
            'public'
        );

        $this->output->newline();

        $this->output->writeln('<yellow>You have chosen:</yellow>');

        $this->output->writeln('  root namespace:     <cyan>' . $namespace . '</cyan> (classes such as \\' . $namespace . '\\Controller)');
        $this->output->writeln('  source directory:   <cyan>' . $sourceDir . '</cyan> (your project files will be loaded from here)');
        $this->output->writeln('  public directory:   <cyan>' . $publicDir . '</cyan> (index.php will be situated here and is the place for your css/js)');

        $sourceDirFull = $this->path->getPath($sourceDir);
        $publicDirFull = $this->path->getPath($publicDir);

        $this->output->newline();

        $this->output->writeln('<yellow>The following directories will now be created (if non-existent):</yellow>');
        $this->output->writeln('  ' .  $sourceDirFull);
        $this->output->writeln('  ' .  $publicDirFull);

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

        $this->output->write('Updating composer.json with autoload for ' . $namespace . '... ');

        $composerJson = file_get_contents(BASEDIR . '/composer.json');
        $composerArray = json_decode($composerJson, true);


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
        if (!in_array('parable_init.php', $composerArray['autoload']['files'])) {
            $composerArray['autoload']['files'][] = 'parable_init.php';
        }

        $composerJson = json_encode($composerArray, JSON_PRETTY_PRINT);
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

        if ($upgrading === false) {
            if ($this->askUserToContinue(
                'Do you want to install the example files (Boot.php, welcome.phtml)? (say no if you\'re upgrading)',
                true
            )) {
                if (!$this->copyTemplateFile('Boot.php', $sourceDir, $namespace, $sourceDir, $publicDir)) {
                    return;
                }
                if (!$this->copyTemplateFile('welcome.phtml', $sourceDir, $namespace, $sourceDir, $publicDir)) {
                    return;
                }
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

            if (!mkdir($path)) {
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
        $structurePath = realpath(__DIR__ . DS . '..' . DS . '..' . DS . 'structure');

        $contents = @file_get_contents($structurePath . DS . $filename . '_template');

        if ($contents === false) {
            $this->output->writeln('<error>Could not read file!</error>');
            return null;
        }

        $contents = str_replace('###ROOT_NAMESPACE###', $namespace, $contents);
        $contents = str_replace('###SOURCE_DIRECTORY###', $sourceDir, $contents);
        $contents = str_replace('###PUBLIC_DIRECTORY###', $publicDir, $contents);

        return $contents;
    }
}
