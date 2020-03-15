<?php
namespace Rhaarhoff\Workflow\Console\Workflow;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Rhaarhoff\Workflow\Helpers\Utility;
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
     * Definition file required fields
     */
    const DEFINITION_FILE_FIELD_NAME = 'name';
    const DEFINITION_FILE_FIELD_USES = 'uses';
    const DEFINITION_FILE_FIELD_NAMESPACE = 'namespace';
    const DEFINITION_FILE_FIELD_START_STATE = 'startState';
    const DEFINITION_FILE_FIELD_INPUT = 'input';
    const DEFINITION_FILE_FIELD_OUTPUT = 'output';
    const DEFINITION_FILE_FIELD_WORKFLOW = 'workflow';
    const DEFINITION_FILE_REQUIRED_FIELDS = [
        self::DEFINITION_FILE_FIELD_NAME,
        self::DEFINITION_FILE_FIELD_USES,
        self::DEFINITION_FILE_FIELD_NAMESPACE,
        self::DEFINITION_FILE_FIELD_START_STATE,
        self::DEFINITION_FILE_FIELD_INPUT,
        self::DEFINITION_FILE_FIELD_OUTPUT,
        self::DEFINITION_FILE_FIELD_WORKFLOW,
    ];

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
            $this->assertWorkflowValid($name);
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
    private function assertWorkflowValid(string $inputName)
    {
        $workflowFolderName = Utility::formatTextToSnakeCase($inputName);
        $workflowFilePath = $this->getPath($inputName) . $workflowFolderName;

        if (Utility::fileExists($workflowFilePath)) {
            $this->assertWorkflowHasAllValidDefinitionFile($workflowFilePath);
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
    private function assertWorkflowHasAllValidDefinitionFile(string $filePath)
    {
        $definitionFilePath = $filePath . '/definition/';

        $allFileInPath = Utility::getAllFileInPath($definitionFilePath);

        if (Utility::isArrayEmpty($allFileInPath)) {
            $this->error(vsprintf(self::ERROR_NO_DEFINITION_FILE_IN_PATH, [$definitionFilePath]));
        } else {
            $this->assertAllFileInPathValid($definitionFilePath, $allFileInPath);
        }
    }

    /**
     * @param string $definitionFilePath
     * @param string[] $allFileInPath
     *
     * @throws FileNotFoundException
     */
    private function assertAllFileInPathValid(string $definitionFilePath, array $allFileInPath)
    {
        foreach ($allFileInPath as $file) {
            $fullFilePath = $definitionFilePath . $file;

            if (Utility::isFileValidJson($fullFilePath)) {
                $this->assertDefinitionFileValid($fullFilePath);
            } else {
                $this->error(vsprintf(self::ERROR_FILE_NOT_JSON, [$fullFilePath]));
            }
        }
    }

    /**
     * @param string $fullFilePath
     */
    private function assertDefinitionFileValid(string $fullFilePath)
    {
        $fileContent = json_decode(file_get_contents($fullFilePath), true);

        $this->assertHasAllRequiredField($fileContent, $fullFilePath);

        foreach (self::DEFINITION_FILE_REQUIRED_FIELDS as $field) {
            $this->assertFieldContentValid($fileContent, $fileContent[$field], $field);
        }
    }

    /**
     * @param array $fileContent
     * @param string $fullFilePath
     */
    private function assertHasAllRequiredField(array $fileContent, string $fullFilePath) {
        foreach (self::DEFINITION_FILE_REQUIRED_FIELDS as $field) {
            if (isset($fileContent[$field])) {
                // Do nothing
            } else {
                $this->error(
                    vsprintf(self::ERROR_DEFINITION_FIELD_MISSING,
                        [
                            $field,
                            $fullFilePath,
                        ]
                    )
                );
            }
        }
    }

    /**
     * @param $fileContent
     * @param $fieldContent
     * @param string $field
     */
    private function assertFieldContentValid($fileContent, $fieldContent, $field)
    {
        // TODO: Implement assertions on each field's content
        switch ($field) {
            case self::DEFINITION_FILE_FIELD_NAME:
                $this->assertFieldNameValid($fieldContent);
                break;
            case self::DEFINITION_FILE_FIELD_USES:
                $this->assertAllImportClassExists($fieldContent);
                break;
            case self::DEFINITION_FILE_FIELD_NAMESPACE:
                var_dump('Checking NameSpace field content');
                break;
            case self::DEFINITION_FILE_FIELD_INPUT:
                $this->assertInputHasFieldSet($fieldContent);
                $this->assertInputAllFieldValid($fileContent, $fieldContent);
                break;
            case self::DEFINITION_FILE_FIELD_OUTPUT:
                $this->assertOutputHasFieldSet($fieldContent);
                $this->assertOutputAllFieldValid($fileContent, $fieldContent);
                break;
            case self::DEFINITION_FILE_FIELD_START_STATE:
                $this->assertStartStateValid($fieldContent);
                break;
            case self::DEFINITION_FILE_FIELD_WORKFLOW:
                var_dump('Checking Workflow content');
                $this->assertAllWorkflowStateValid($fileContent, $fieldContent);
                break;
        }
    }

    /**
     * @param string $name
     */
    private function assertFieldNameValid(string $name)
    {
        if (preg_match('/^[A-Z][a-zA-Z0-9]+$/', $name)) {
            // Do nothing
        } else {
            $this->error('Error - Invalid definition name: ' . $name);
        }
    }

    /**
     * @param string[] $allImport
     */
    private function assertAllImportClassExists(array $allImport)
    {
        foreach ($allImport as $import) {
            if (class_exists($import)) {
                // Do nothing
            } else {
                $this->error('Error - Class does not exist for import: ' . $import);
            }
        }
    }

    /**
     * @param string[] $allInputField
     */
    private function assertInputHasFieldSet(array $allInputField)
    {
        if (Utility::countNumberOfElementInArray($allInputField) === 0) {
            $this->error('Error - No input parameters specified.');
        }
    }

    /**
     * @param array $fileContent
     * @param array $allInputField
     */
    private function assertInputAllFieldValid(array $fileContent, array $allInputField)
    {
        foreach ($allInputField as $inputFieldName => $inputFieldClass) {
            $this->assertInputFieldNameValid($inputFieldName);
            $this->assertFieldClassNameValid($inputFieldClass);
            $this->assertFieldClassImportSpecified(
                $fileContent[self::DEFINITION_FILE_FIELD_USES],
                $inputFieldName,
                $inputFieldClass
            );
        }
    }

    /**
     * @param string[] $allOutputField
     */
    private function assertOutputHasFieldSet(array $allOutputField)
    {
        if (Utility::countNumberOfElementInArray($allOutputField) === 1) {
            // Do nothing
        } else {
            $this->error('Error - There should only be one output field set.');
        }
    }

    /**
     * @param array $fileContent
     * @param array $allOutputField
     */
    private function assertOutputAllFieldValid(array $fileContent, array $allOutputField)
    {
        foreach ($allOutputField as $outputFieldName => $outputFieldClass) {
            $this->assertOutputFieldNameValid($outputFieldName);
            $this->assertFieldClassNameValid($outputFieldClass);
            $this->assertFieldClassImportSpecified(
                $fileContent[self::DEFINITION_FILE_FIELD_USES],
                $outputFieldName,
                $outputFieldClass
            );
        }
    }

    /**
     * @param string $fieldName
     */
    private function assertInputFieldNameValid(string $fieldName)
    {
        if (preg_match('/^[a-z][a-zA-Z0-9]*$/', $fieldName)) {
            // Do nothing
        } else {
            $this->error('Error - Invalid input field name: ' . $fieldName . '. Should start with a lowercase letter and must be alphanumeric.');
        }
    }

    /**
     * @param string $fieldName
     */
    private function assertOutputFieldNameValid(string $fieldName)
    {
        if (preg_match('/^[a-z][a-zA-Z0-9]*$/', $fieldName)) {
            // Do nothing
        } else {
            $this->error('Error - Invalid output field name: ' . $fieldName . '. Should start with a lowercase letter and must be alphanumeric.');
        }
    }

    /**
     * @param string $fieldClass
     */
    private function assertFieldClassNameValid(string $fieldClass)
    {
        if (preg_match('/^[A-Z][a-zA-Z0-9]*$/', $fieldClass)) {
            // Do nothing
        } else {
            $this->error('Error - Invalid class name: ' . $fieldClass . '. Should start with an uppercase letter and must be alphanumeric.');
        }
    }

    /**
     * @param string $startState
     */
    private function assertStartStateValid(string $startState)
    {
        if (preg_match('/^[A-Z][a-zA-Z0-9]*$/', $startState)) {
            // Do nothing
        } else {
            $this->error('Error - Invalid start state name: ' . $startState . '. Should start with an uppercase letter and must be alphanumeric.');
        }
    }

    /**
     * @param array $allImport
     * @param string $inputFieldName
     * @param string $inputFieldClass
     * @return bool
     */
    private function assertFieldClassImportSpecified(
        array $allImport,
        string $inputFieldName,
        string $inputFieldClass
    ): bool {
        foreach ($allImport as $import) {
            $allImportPart = explode('\\', $import);

            $lastImportPart = end($allImportPart);

            if ($lastImportPart === $inputFieldClass) {
                return true;
            }
        }

        $this->error('Error - Import is missing for input: ' . $inputFieldName . '.');

        return false;
    }

    /**
     * @param array $fileContent
     * @param array $allWorkflowState
     */
    private function assertAllWorkflowStateValid(array $fileContent, array $allWorkflowState)
    {
        $this->assertWorkflowHasStateSet($allWorkflowState);
        $this->assertWorkflowStartStateValid(
            $fileContent[self::DEFINITION_FILE_FIELD_START_STATE],
            $allWorkflowState
        );

        foreach ($allWorkflowState as $workflowState) {
            // TODO: assert each workflow state is valid
        }
    }

    /**
     * @param string $startState
     * @param array $allWorkflowState
     */
    private function assertWorkflowStartStateValid(string $startState, array $allWorkflowState)
    {
        if ($startState === array_key_first($allWorkflowState)) {
            // Do nothing
        } else {
            $this->error('Error - start state specified in workflow does not match: ' . $startState);
        }
    }

    /**
     * @param string[] $allWorkflowState
     */
    private function assertWorkflowHasStateSet(array $allWorkflowState)
    {
        if (Utility::countNumberOfElementInArray($allWorkflowState) === 0) {
            $this->error('Error - No workflow states defined.');
        }
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
