<?php
namespace Rhaarhoff\Workflow\Console\Workflow;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Symfony\Component\Console\Input\InputOption;

/**
 * @author Ruan Haarhoff <ruan@aptic.com>
 * @since 20200208 Initial creation.
 */
class CreateWorkflow extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'workflow:create';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new workflow definition';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $type = 'Workflow Definition';

    /**
     * The base directories generated with the definition file.
     *
     * @var string[]
     */
    protected $baseDirectories = [
        'Code',
        'Generated'
    ];

    /**
     * Type of file we are generating for the definition of a Workflow.
     *
     * @var string
     */
    protected $fileType = '.json';

    /**
     * Option constants.
     */
    const OPTION_START_FULL_NAME = 'start';
    const OPTION_START_SHORT_NAME = 's';
    const OPTION_START_DESCRIPTION = 'The start state of the workflow.';

    /**
     * File Path constants.
     */
    const FILE_PATH_WORKFLOW_DEFINITION_SIMPLE_STUB = __DIR__ . '/stubs/workflow.definition.simple.stub';

    /**
     * Execute the console command.
     *
     * @return bool|null
     * @throws FileNotFoundException
     */
    public function handle()
    {
        $name = $this->qualifyClass($this->getNameInput());

        $path = $this->getPath($name) . $this->fileType;

        if ($this->isOptionForcedUnset() && $this->alreadyExists($this->getNameInput())) {
            $this->error($this->type.' already exists!');

            return false;
        }

        $this->makeDirectory($path);
        $this->makeBaseDirectories();

        $this->files->put($path, $this->sortImports($this->buildClass($name)));

        $this->info($this->type . ' ' . $this->getNameInput() . ' definition created successfully.');

        return true;
    }

    /**
     * @return bool
     */
    private function isOptionForcedUnset(): bool
    {
        return (!$this->hasOption('force') || !$this->option('force'));
    }

    /**
     * @param string $rawName
     *
     * @return bool
     */
    protected function alreadyExists($rawName): bool
    {
        $path = $this->getPath($this->qualifyClass($rawName)) . $this->fileType;

        return $this->files->exists($path);
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions(): array
    {
        return [
            [
                self::OPTION_START_FULL_NAME,
                self::OPTION_START_SHORT_NAME,
                InputOption::VALUE_OPTIONAL,
                self::OPTION_START_DESCRIPTION,
            ],
        ];
    }

    /**
     * @return string
     */
    public function getStub(): string
    {
        return self::FILE_PATH_WORKFLOW_DEFINITION_SIMPLE_STUB;
    }

    /**
     * Parse the class name and format according to the root namespace.
     *
     * @param string $name
     *
     * @return string
     */
    protected function qualifyClass($name): string
    {
        $name = ltrim($name, '\\/');

        $rootNamespace = $this->rootNamespace();

        if (Str::startsWith($name, $rootNamespace)) {
            return $name;
        }

        $name = str_replace('/', '\\', $name);

        return $this->qualifyClass(
            $this->getDefaultNamespace(trim($rootNamespace, '\\')).'\\Definition\\'.$name
        );
    }

    /**
     * @param string $rootNamespace
     *
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace): string
    {
        return $this->getWorkflowParentFolder($rootNamespace);
    }

    /**
     * @param $rootNamespace
     *
     * @return string
     */
    private function getWorkflowParentFolder($rootNamespace): string
    {
        return $rootNamespace.'\Workflows\\' . $this->formatWorkflowFolderName();
    }

    /**
     * @return string
     */
    private function formatWorkflowFolderName(): string
    {
        return $this->getNameInput();
    }

    /**
     * Build the class with the given name.
     *
     * Remove the base controller import if we are already in base namespace.
     *
     * @param string $name
     * @return mixed|string
     * @throws FileNotFoundException
     */
    protected function buildClass($name)
    {
        $inputName = $this->getNameInput();

        $this->assertNameInputValid($inputName);

        $replace = [];

        $replace = $this->buildWorkflowNameSpace($replace);

        $replace = $this->buildWorkflowName($replace, $inputName);

        $replace = $this->buildWorkflowStartState($replace);

        return str_replace(
            array_keys($replace), array_values($replace), parent::buildClass($name)
        );
    }

    /**
     * @param string $inputName
     */
    private function assertNameInputValid(string $inputName)
    {
        if (preg_match('/^[A-Z][a-zA-Z0-9]+$/', $inputName)) {
            // Do nothing
        } else {
            throw new InvalidArgumentException(
                'Invalid name: ' . $inputName . '. Name should be camel case and only contain alphanumeric characters.'
            );
        }
    }

    /**
     * @param string[] $replace
     * @param string $inputName
     *
     * @return string[]
     */
    protected function buildWorkflowName(array $replace, $inputName): array
    {
        $replace['WorkflowName'] = $inputName;

        return $replace;
    }

    /**
     * @param string[] $replace
     *
     * @return string[]
     */
    protected function buildWorkflowStartState(array $replace): array
    {
        if ($this->option('start')) {
            $start = $this->parseInputFieldAlphaNumeric($this->option('start'));

            $replace['WorkflowStartState'] = $start;
        } else {
            $replace['WorkflowStartState'] = 'start_state_function_name';
        }

        return $replace;
    }

    /**
     * @param string[] $replace
     *
     * @return string[]
     */
    protected function buildWorkflowNameSpace(array $replace): array
    {
        $replace['WorkflowNameSpace'] = 'App\\\\Workflows\\\\' . $this->getNameInput();

        return $replace;
    }

    /**
     * @param $input
     *
     * @return string
     * @throws InvalidArgumentException
     */
    protected function parseInputFieldAlphaNumeric($input)
    {
        if (preg_match('([^A-Za-z0-9_/\\\\])', $input)) {
            throw new InvalidArgumentException('Start state contains invalid characters.');
        }

        return $input;
    }

    /**
     * Get the destination class path.
     *
     * @param  string  $name
     * @return string
     */
    protected function getPath($name)
    {
        $name = Str::replaceFirst($this->rootNamespace(), '', $name);

        return $this->laravel['path'].'/'.str_replace('\\', '/', $name);
    }

    /**
     * Build the directory for the class if necessary.
     *
     * @param  string  $path
     * @return string
     */
    protected function makeDirectory($path)
    {
        if (! $this->files->isDirectory(dirname($path))) {

            $this->files->makeDirectory(dirname($path), 0777, true, true);
        }

        return $path;
    }

    /**
     */
    protected function makeBaseDirectories()
    {
        foreach ($this->baseDirectories as $directory) {
            $qualifyClass = $this->qualifyClass(
                $this->getDefaultNamespace(trim($this->rootNamespace(), '\\')) . '\\' . $directory
            );
            $path = $this->getPath($qualifyClass);

            $this->files->makeDirectory($path, 0777, true, true);
        }
    }
}
