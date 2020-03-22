<?php
namespace Rhaarhoff\Workflow\Console\Workflow;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Rhaarhoff\Workflow\Helpers\Utility;
use Rhaarhoff\Workflow\Validator\ValidateWorkflow;
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
    const FILE_PATH_WORKFLOW_SIMPLE_STUB = __DIR__ . '/stubs/workflow.simple.stub';

    /**
     * @return bool|null
     * @throws FileNotFoundException
     */
    public function handle()
    {
        $name = $this->getNameInputOrNull();

        if (is_null($name)) {
            // TODO: generate all workflows
            $this->info('Generating all workflows.');
        } else {
            // Generate only the specified workflow
            $this->info('Generating workflows for ' . Utility::formatTextToSnakeCase($name));

            $this->assertInputNameValid($name);
            $this->generateValidWorkflow($name);
        }

        return true;
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
        $workflowFolderName = Utility::formatTextToSnakeCase($inputName);
        $workflowFilePath = $this->getPath($inputName) . $workflowFolderName;

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
        $definitionFilePath = $filePath . '/definition/';

        $allFileInPath = Utility::getAllFileInPath($definitionFilePath);

        if (Utility::isArrayEmpty($allFileInPath)) {
            $this->error(vsprintf(self::ERROR_NO_DEFINITION_FILE_IN_PATH, [$definitionFilePath]));
        } else {
            $this->generateAllValidFileInPath($definitionFilePath, $allFileInPath);
        }
    }

    /**
     * @param string $definitionFilePath
     * @param string[] $allFileInPath
     */
    private function generateAllValidFileInPath(string $definitionFilePath, array $allFileInPath)
    {
        foreach ($allFileInPath as $file) {
            $fullFilePath = $definitionFilePath . $file;

            if (Utility::isFileValidJson($fullFilePath)) {
                $validateWorkflow = $this->assertDefinitionFileValid($fullFilePath);

                $allError = $validateWorkflow->getError();

                if (Utility::isArrayEmpty($allError)) {
                    // TODO: Generate the valid workflow.
                } else {
                    $this->showAllDefinitionFileError($allError, $fullFilePath);
                }
            } else {
                $this->error(vsprintf(self::ERROR_FILE_NOT_JSON, [$fullFilePath]));
            }
        }
    }

    /**
     * @param string[] $allError
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
     * @return string
     */
    public function getStub(): string
    {
        return self::FILE_PATH_WORKFLOW_SIMPLE_STUB;
    }

    /**
     * Get the destination class path.
     *
     * @param  string  $name
     * @return string
     */
    protected function getPath($name)
    {
        return $this->laravel['path'] . '/Workflows/';
    }
}
