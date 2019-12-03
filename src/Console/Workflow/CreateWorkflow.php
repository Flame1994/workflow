<?php
namespace Rhaarhoff\Workflow\Console\Workflow;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Symfony\Component\Console\Input\InputOption;

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
    protected $description = 'Create a new workflow';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $type = 'Workflow';

    /**
     * The base directories generated with the definition file.
     *
     * @var string[]
     */
    protected $baseDirectories = [
        'code',
        'generated'
    ];

    /**
     * Type of file we are generating for the definition of a Workflow.
     *
     * @var string
     */
    protected $fileType = '.json';

    /**
     * Execute the console command.
     *
     * @return bool|null
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function handle()
    {
        $name = $this->qualifyClass($this->getNameInput());

        $path = $this->getPath($name) . $this->fileType;

        // First we will check to see if the class already exists. If it does, we don't want
        // to create the class and overwrite the user's code. So, we will bail out so the
        // code is untouched. Otherwise, we will continue generating this class' files.
        if ((! $this->hasOption('force') ||
                ! $this->option('force')) &&
            $this->alreadyExists($this->getNameInput())) {
            $this->error($this->type.' already exists!');

            return false;
        }

        // Next, we will generate the path to the location where this class' file should get
        // written. Then, we will build the class and make the proper replacements on the
        // stub files so that it gets the correctly formatted namespace and class name.
        $this->makeDirectory($path);
        $this->makeBaseDirectories();

        $this->files->put($path, $this->sortImports($this->buildClass($name)));

        $this->info($this->type . ' ' . $this->getNameInput() . ' definition created successfully.');

        return true;
    }

    /**
     * Determine if the class already exists.
     *
     * @param  string  $rawName
     * @return bool
     */
    protected function alreadyExists($rawName)
    {
        $path = $this->getPath($this->qualifyClass($rawName)) . $this->fileType;

        return $this->files->exists($path);
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['start', 's', InputOption::VALUE_OPTIONAL, 'The start state of the workflow.']
        ];
    }

    public function getStub() {
        return __DIR__ . '/stubs/workflow.simple.stub';
    }

    /**
     * Parse the class name and format according to the root namespace.
     *
     * @param  string  $name
     * @return string
     */
    protected function qualifyClass($name)
    {
        $name = ltrim($name, '\\/');

        $rootNamespace = $this->rootNamespace();

        if (Str::startsWith($name, $rootNamespace)) {
            return $name;
        }

        $name = str_replace('/', '\\', $name);

        return $this->qualifyClass(
            $this->getDefaultNamespace(trim($rootNamespace, '\\')).'\\definition\\'.$name
        );
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return $this->getWorkflowParentFolder($rootNamespace);
    }

    /**
     * @param $rootNamespace
     *
     * @return string
     */
    private function getWorkflowParentFolder($rootNamespace) {
        return $rootNamespace.'\Workflows\\' .
            $this->formatWorkflowFolderName();
    }

    /**
     * @return string
     */
    private function formatWorkflowFolderName(): string
    {
        $folderName = preg_replace('/(?<=\\w)(?=[A-Z])/',"_$1", $this->getNameInput());

        return strtolower($folderName);
    }

    /**
     * Build the class with the given name.
     *
     * Remove the base controller import if we are already in base namespace.
     *
     * @param string $name
     * @return mixed|string
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function buildClass($name)
    {
        $inputName = $this->getNameInput();

        $replace = [];

        $replace = $this->buildWorkflowNameSpace($replace);

        $replace = $this->buildWorkflowName($replace, $inputName);

        $replace = $this->buildWorkflowStartState($replace);



        return str_replace(
            array_keys($replace), array_values($replace), parent::buildClass($name)
        );
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

    protected function buildWorkflowNameSpace(array $replace): array
    {
        $replace['WorkflowNameSpace'] = 'App\\\\Workflows\\\\' . $this->formatWorkflowFolderName();

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
