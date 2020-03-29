<?php
namespace Rhaarhoff\Workflow\Console\Workflow;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Rhaarhoff\Workflow\Helpers\Utility;
use Rhaarhoff\Workflow\Validator\ValidateWorkflow;
use Rhaarhoff\Workflow\Generator\ConstructWorkflowBase;
use Rhaarhoff\Workflow\Generator\ConstructWorkflow;
use Symfony\Component\Console\Input\InputArgument;

/**
 * @author Ruan Haarhoff <ruan@aptic.com>
 * @since 20200208 Initial creation.
 */
class GenerateWorkflow extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'workflow:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new workflow from all or specified definitions';

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
        'Code',
        'Generated'
    ];

    /**
     * The default directories we which to ignore when scanning all workflow folders
     *
     * @var string[]
     */
    protected $defaultDirectories = [
        '.',
        '..',
        'Common'
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
    const OPTION_NAME_FULL_NAME = 'name';
    const OPTION_NAME_SHORT_NAME = 'n';
    const OPTION_NAME_DESCRIPTION = 'The name of the workflow.';

    /**
     * Error constants
     */
    const ERROR_WORKFLOW_FOLDER_DOES_NOT_EXIST =
        'Workflow folder does not exist on path "%s".';
    const ERROR_NO_DEFINITION_FILE_IN_PATH = 'No definition files found in path "%s".';
    const ERROR_FILE_NOT_JSON = 'File "%s" is not a valid json file.';
    const ERROR_DEFINITION_FIELD_MISSING = 'Field "%s" is missing from the definition file "%s".';

    /**
     * File Path constants.
     */
    const FILE_PATH_WORKFLOW_BASE_STUB = __DIR__ . '/stubs/workflow.base.stub';
    const FILE_PATH_WORKFLOW_COMMON_STUB = __DIR__ . '/stubs/workflow.common.stub';
    const FILE_PATH_WORKFLOW_CODE_STUB = __DIR__ . '/stubs/workflow.code.stub';

    /**
     * @return bool|null
     * @throws FileNotFoundException
     */
    public function handle()
    {
        $name = $this->getNameInputOrNull();

        if (is_null($name)) {
            $allWorkflowName = $this->getAllWorkflowName();
            $this->info('Starting workflow code generation.');

            foreach ($allWorkflowName as $name) {
                $this->generateWorkflowByName($name);
            }
        } else {
            $this->assertInputNameValid($name);
            $this->generateWorkflowByName($name);
        }

        return true;
    }

    /**
     * @param string $name
     *
     * @throws FileNotFoundException
     */
    private function generateWorkflowByName(string $name)
    {
        $this->info('Generating workflows for ' . $name);
        $this->generateValidWorkflow($name);
    }

    /**
     * @return string[]
     */
    private function getAllWorkflowName(): array
    {
        $allWorkflowName = [];

        $path = $this->getPath('');

        $allFile = scandir($path);

        foreach ($allFile as $file) {
            if (in_array($file, $this->defaultDirectories)) {
                // Do nothing
            } else {
                $allWorkflowName[] = $file;
            }
        }

        return $allWorkflowName;
    }

    /**
     * Get the desired class name from the input.
     *
     * @return string
     */
    protected function getNameInputOrNull()
    {
        if ($this->argument('name')) {
            return trim($this->argument('name'));
        } else {
            return null;
        }
    }

    /**
     * @param string $inputName
     */
    private function assertInputNameValid(string $inputName)
    {
        Utility::assertIsAlphaNumeric($inputName);
    }

    /**
     * @param string $inputName
     *
     * @throws FileNotFoundException
     */
    private function generateValidWorkflow(string $inputName)
    {
        $workflowFilePath = $this->getPath($inputName) . $inputName;

        if (Utility::fileExists($workflowFilePath)) {
            $this->generateAllValidWorkflowDefinitionFile($workflowFilePath);
        } else {
            $this->error(
                vsprintf(self::ERROR_WORKFLOW_FOLDER_DOES_NOT_EXIST,
                     [
                         $workflowFilePath,
                     ]
                )
            );
        }
    }

    /**
     * @param string $filePath
     *
     * @throws FileNotFoundException
     */
    private function generateAllValidWorkflowDefinitionFile(string $filePath)
    {
        $definitionFilePath = $filePath . '/Definition/';

        $allFileInPath = Utility::getAllFileInPath($definitionFilePath);

        if (Utility::isArrayEmpty($allFileInPath)) {
            $this->error(vsprintf(self::ERROR_NO_DEFINITION_FILE_IN_PATH, [$definitionFilePath]));
        } else {
            $this->generateAllValidFileInPath($filePath, $definitionFilePath, $allFileInPath);
        }
    }

    /**
     * @param string $workflowFolderPath
     * @param string $definitionFilePath
     * @param string[] $allFileInPath
     *
     * @throws FileNotFoundException
     */
    private function generateAllValidFileInPath(
        string $workflowFolderPath,
        string $definitionFilePath,
        array $allFileInPath
    ) {
        foreach ($allFileInPath as $file) {
            $fullFilePath = $definitionFilePath . $file;

            if (Utility::isFileValidJson($fullFilePath)) {
                $validateWorkflow = $this->assertDefinitionFileValid($fullFilePath);

                $allError = $validateWorkflow->getError();

                if (Utility::isArrayEmpty($allError)) {
                    $this->generateWorkflow($workflowFolderPath, $fullFilePath);
                } else {
                    $this->showAllDefinitionFileError($allError, $fullFilePath);

                    break;
                }
            } else {
                $this->error(vsprintf(self::ERROR_FILE_NOT_JSON, [$fullFilePath]));

                break;
            }
        }
    }

    /**
     * @param string $workflowFolderPath
     * @param string $fullFilePath
     */
    private function generateWorkflow(string $workflowFolderPath, string $fullFilePath)
    {
        $definitionFileContent = json_decode(file_get_contents($fullFilePath), true);

        $this->generateWorkflowCommonIfNeeded();
        $this->generateWorkflowBase($definitionFileContent, $workflowFolderPath);
        $this->generateWorkflowCodeIfNeeded($definitionFileContent, $workflowFolderPath);
    }

    /**
     */
    private function generateWorkflowCommonIfNeeded()
    {
        $path = $this->laravel['path'] . '/Workflows/Common';

        if ($this->workflowCommonAlreadyExists($path)) {
            // Do nothing.
        } else {
            $this->files->makeDirectory($path, 0777, true, true);
            $this->files->put(
                $path . '/Workflow.php',
                $this->constructInitialClassCommon()
            );
        }
    }

    /**
     * @param string $path
     *
     * @return bool
     */
    protected function workflowCommonAlreadyExists(string $path): bool
    {
        return Utility::fileExists($path);
    }

    /**
     * @param string[] $definitionFileContent
     * @param string $workflowFolderPath
     */
    private function generateWorkflowBase(array $definitionFileContent, string $workflowFolderPath)
    {
        $workflowName = $definitionFileContent['name'];
        $workflowPath = $workflowFolderPath . '/Generated/Workflow' . $workflowName . 'Base.php';

        $this->files->put(
            $workflowPath,
            $this->sortImports($this->constructWorkflowBaseClass($definitionFileContent))
        );
    }

    /**
     * @param string[] $definitionFileContent
     * @param string $workflowFolderPath
     */
    private function generateWorkflowCodeIfNeeded(array $definitionFileContent, string $workflowFolderPath)
    {
        $workflowName = $definitionFileContent['name'];
        $workflowPath = $workflowFolderPath . '/Code/Workflow' . $workflowName . '.php';

        if (Utility::fileExists($workflowPath)) {
            // Do nothing
        } else {
            $this->files->put(
                $workflowPath,
                $this->sortImports($this->constructWorkflowCodeClass($definitionFileContent))
            );
        }
    }

    /**
     * @param string[] $allError
     * @param string $fullFilePath
     */
    private function showAllDefinitionFileError(array $allError, string $fullFilePath)
    {
        $this->warn($fullFilePath);

        foreach ($allError as $error) {
            $this->error($error);
        }
    }

    /**
     * @param string $fullFilePath
     * @return ValidateWorkflow
     */
    private function assertDefinitionFileValid(string $fullFilePath): ValidateWorkflow
    {
        $fileContent = json_decode(file_get_contents($fullFilePath), true);

        return new ValidateWorkflow($fileContent, $fullFilePath);
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [];
    }

    /**
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['name', InputArgument::OPTIONAL, 'The name of the workflow']
        ];
    }

    /**
     * @param string[] $fileContent
     * @return string|string[]
     */
    protected function constructWorkflowBaseClass(array $fileContent)
    {
        $constructedWorkflow = new ConstructWorkflowBase($fileContent);

        $replace = $constructedWorkflow->getAllContentReplace();

        return str_replace(
            array_keys($replace), array_values($replace), $this->constructInitialClassBase()
        );
    }

    /**
     * @param string[] $fileContent
     * @return string|string[]
     */
    protected function constructWorkflowCodeClass(array $fileContent)
    {
        $constructedWorkflow = new ConstructWorkflow($fileContent);

        $replace = $constructedWorkflow->getAllContentReplace();

        return str_replace(
            array_keys($replace), array_values($replace), $this->constructInitialClass()
        );
    }

    /**
     * @return string
     * @throws FileNotFoundException
     */
    private function constructInitialClassBase(): string
    {
        return $this->files->get($this->getBaseStub());
    }

    /**
     * @return string
     * @throws FileNotFoundException
     */
    private function constructInitialClass(): string
    {
        return $this->files->get($this->getCodeStub());
    }

    /**
     * @return string
     * @throws FileNotFoundException
     */
    private function constructInitialClassCommon(): string
    {
        return $this->files->get($this->getCommonStub());
    }

    /**
     */
    protected function getStub()
    {
        // Do nothing - using custom stub methods.
    }

    /**
     * @return string
     */
    public function getBaseStub(): string
    {
        return self::FILE_PATH_WORKFLOW_BASE_STUB;
    }

    /**
     * @return string
     */
    public function getCommonStub(): string
    {
        return self::FILE_PATH_WORKFLOW_COMMON_STUB;
    }

    /**
     * @return string
     */
    public function getCodeStub(): string
    {
        return self::FILE_PATH_WORKFLOW_CODE_STUB;
    }

    /**
     * @param string $name
     *
     * @return string
     */
    protected function getPath($name): string
    {
        return $this->laravel['path'] . '/Workflows/';
    }
}
