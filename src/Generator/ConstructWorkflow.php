<?php
namespace Rhaarhoff\Workflow\Generator;

use Rhaarhoff\Workflow\Helpers\Utility;

class ConstructWorkflow
{
    /**
     * Workflow file replace constants.
     */
    const REPLACE_DEFAULT_WORKFLOW_NAMESPACE = 'DefaultWorkflowNameSpace';
    const REPLACE_DEFAULT_WORKFLOW_ALL_IMPORT = 'DefaultWorkflowAllImport';
    const REPLACE_DEFAULT_WORKFLOW_NAME = 'DefaultWorkflowName';
    const REPLACE_DEFAULT_WORKFLOW_NAME_BASE = 'DefaultWorkflowNameBase';
    const REPLACE_DEFAULT_WORKFLOW_EXECUTE_DOC_BLOCK = 'DefaultWorkflowExecuteDocBlock';
    const REPLACE_DEFAULT_WORKFLOW_ALL_INPUT_PARAMETER = 'DefaultWorkflowAllInputParameter';
    const REPLACE_DEFAULT_WORKFLOW_OUTPUT_TYPE = 'DefaultWorkflowOutputType';
    const REPLACE_DEFAULT_WORKFLOW_ALL_INPUT_DATA = 'DefaultWorkflowAllInputData';

    /**
     * Definition file fields
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
     * Workflow state required fields
     */
    const WORKFLOW_STATE_FIELD_PARAMETERS = 'parameters';
    const WORKFLOW_STATE_FIELD_RESULT = 'result';
    const WORKFLOW_STATE_FIELD_TRANSITION = 'transition';

    /**
     * Workflow state constants
     */
    const WORKFLOW_TRANSITION_END = 'End';

    /**
     * Regex constants
     */
    const REGEX_ALPHANUMERIC_START_UPPERCASE = '/^[A-Z][a-zA-Z0-9]*$/';
    const REGEX_ALPHANUMERIC_START_LOWERCASE = '/^[a-z][a-zA-Z0-9]*$/';

    /**
     * @var $allError
     */
    protected $allError = [];

    /**
     * @var $allField
     */
    protected $allField = [];

    /**
     * @var $allInputField
     */
    protected $allInputField = [];

    /**
     * @var $allContentReplace
     */
    protected $allContentReplace = [
        self::REPLACE_DEFAULT_WORKFLOW_NAMESPACE => 'DefaultWorkflowNameSpace',
        self::REPLACE_DEFAULT_WORKFLOW_ALL_IMPORT => 'DefaultWorkflowAllImport',
        self::REPLACE_DEFAULT_WORKFLOW_NAME => 'DefaultWorkflowName',
        self::REPLACE_DEFAULT_WORKFLOW_NAME_BASE => 'DefaultWorkflowNameBase',
        self::REPLACE_DEFAULT_WORKFLOW_EXECUTE_DOC_BLOCK => 'DefaultWorkflowExecuteDocBlock',
        self::REPLACE_DEFAULT_WORKFLOW_ALL_INPUT_PARAMETER => 'DefaultWorkflowAllInputParameter',
        self::REPLACE_DEFAULT_WORKFLOW_OUTPUT_TYPE => 'DefaultWorkflowOutputType',
        self::REPLACE_DEFAULT_WORKFLOW_ALL_INPUT_DATA => 'DefaultWorkflowAllInputData',
    ];

    /**
     * @param string[] $fileContent
     */
    public function __construct(array $fileContent)
    {
        $this->constructAllWorkflowContent($fileContent);
    }

    /**
     * @return string[]
     */
    public function getAllContentReplace(): array
    {
        return $this->allContentReplace;
    }

    /**
     * @param string $errorString
     */
    private function error(string $errorString)
    {
        $this->allError[] = $errorString;
    }

    /**
     * @return string[]
     */
    public function getError(): array
    {
        return $this->allError;
    }

    /**
     * @param string[] $fileContent
     */
    private function constructAllWorkflowContent(array $fileContent)
    {
        $this->determineAllField($fileContent);

        $this->constructWorkflowContentNameSpace($fileContent);
        $this->constructWorkflowContentAllImport($fileContent);
        $this->constructWorkflowContentName($fileContent);
        $this->constructWorkflowContentNameBase($fileContent);
        $this->constructWorkflowContentExecuteDocBlock($fileContent);
        $this->constructWorkflowContentAllInputParameter($fileContent);
        $this->constructWorkflowContentOutputType($fileContent);
        $this->constructWorkflowContentAllInputData($fileContent);
    }

    /**
     * @param string[] $fileContent
     */
    private function determineAllField(array $fileContent)
    {
        $allField = [];
        $allInputField = [];

        $allInput = $fileContent[self::DEFINITION_FILE_FIELD_INPUT];

        foreach ($allInput as $inputName => $inputType) {
            $allField[$inputName] = $inputType;
            $allInputField[$inputName] = $inputType;
        }

        $allWorkflowStep = $fileContent[self::DEFINITION_FILE_FIELD_WORKFLOW];

        foreach ($allWorkflowStep as $workflowStep) {
            $allResult = $workflowStep[self::WORKFLOW_STATE_FIELD_RESULT];

            foreach ($allResult as $resultName => $resultType) {
                $allField[$resultName] = $resultType;
            }
        }

        $this->allField = $allField;
        $this->allInputField = $allInputField;
    }

    /**
     * @param string[] $fileContent
     */
    private function constructWorkflowContentNameSpace(array $fileContent)
    {
        $nameSpace = $fileContent[self::DEFINITION_FILE_FIELD_NAMESPACE];

        $this->allContentReplace[self::REPLACE_DEFAULT_WORKFLOW_NAMESPACE] = $nameSpace;
    }

    /**
     * @param string[] $fileContent
     */
    private function constructWorkflowContentAllImport(array $fileContent)
    {
        $allImport = $fileContent[self::DEFINITION_FILE_FIELD_USES];

        $allImportString = '';

        foreach ($allImport as $import) {
            $allImportString .= 'use ' . $import . ';' . PHP_EOL;
        }

        $nameSpace = $fileContent[self::DEFINITION_FILE_FIELD_NAMESPACE];

        $allImportString .=
            'use ' . $nameSpace . '\\Generated\\Workflow\\' .
            $this->determineWorkflowBaseName($fileContent) . ';' . PHP_EOL;

        $this->allContentReplace[self::REPLACE_DEFAULT_WORKFLOW_ALL_IMPORT] = $allImportString;
    }

    /**
     * @param string[] $fileContent
     */
    private function constructWorkflowContentName(array $fileContent)
    {
        $name = $fileContent[self::DEFINITION_FILE_FIELD_NAME];

        $this->allContentReplace[self::REPLACE_DEFAULT_WORKFLOW_NAME] = 'Workflow' . $name;
    }

    /**
     * @param string[] $fileContent
     */
    private function constructWorkflowContentNameBase(array $fileContent)
    {
        $this->allContentReplace[self::REPLACE_DEFAULT_WORKFLOW_NAME_BASE] =
            $this->determineWorkflowBaseName($fileContent);
    }

    /**
     * @param string[] $fileContent
     *
     * @return string
     */
    private function determineWorkflowBaseName(array $fileContent): string
    {
        $name = $fileContent[self::DEFINITION_FILE_FIELD_NAME];

        return 'Workflow' . $name . 'Base';
    }

    /**
     * @param string[] $fileContent
     */
    private function constructWorkflowContentExecuteDocBlock(array $fileContent)
    {
        $output = $fileContent[self::DEFINITION_FILE_FIELD_OUTPUT];

        $docBlockString = '/**';

        foreach ($this->allInputField as $fieldName => $fieldType) {
            $docBlockString .= '
     * @param ' . $fieldType . ' $' . $fieldName;
        }

        if (count($this->allInputField) > 0) {
            $docBlockString .= '
     *';
        }

        $docBlockString .= '
     * @return ' . $output[array_key_first($fileContent[self::DEFINITION_FILE_FIELD_OUTPUT])] .'
     *
     * @throws Exception
     */';

        $this->allContentReplace[self::REPLACE_DEFAULT_WORKFLOW_EXECUTE_DOC_BLOCK] = $docBlockString;
    }

    /**
     * @param string[] $fileContent
     */
    private function constructWorkflowContentAllInputParameter(array $fileContent)
    {
        $allInputParameterString = '';

        foreach ($this->allInputField as $fieldName => $fieldType) {
            if ($this->hasTypeNull($fieldType)) {
                $allInputParameterString .= '
        ' . $this->determineType(explode('|', $fieldType)[0]) . ' $' . $fieldName . ' = null';
            } else {
                $allInputParameterString .= '
        ' . $fieldType . ' $' . $fieldName;
            }

            if (array_key_last($this->allInputField) !== $fieldName) {
                $allInputParameterString .= ',';
            } else {
                $allInputParameterString .= PHP_EOL . '    ';
            }
        }

        $this->allContentReplace[self::REPLACE_DEFAULT_WORKFLOW_ALL_INPUT_PARAMETER] = $allInputParameterString;
    }

    /**
     * @param string $fieldType
     *
     * @return bool
     */
    private function hasTypeNull(string $fieldType): bool
    {
        if (strpos($fieldType, '|null') !== false) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param string $inputType
     *
     * @return string
     */
    private function determineType(string $inputType): string
    {
        if ($this->endsWith($inputType, '[]')) {
            return 'array';
        } else {
            return $inputType;
        }
    }

    /**
     * @param $haystack
     * @param $needle
     *
     * @return bool
     */
    private function endsWith($haystack, $needle): bool
    {
        return substr_compare($haystack, $needle, -strlen($needle)) === 0;
    }

    /**
     * @param string[] $fileContent
     */
    private function constructWorkflowContentOutputType(array $fileContent)
    {
        $output = $fileContent[self::DEFINITION_FILE_FIELD_OUTPUT];
        $fieldType = $output[array_key_first($fileContent[self::DEFINITION_FILE_FIELD_OUTPUT])];

        $outputType = '';

        if ($this->hasTypeNull($fieldType)) {
            // Do nothing
        } else {
            $outputType .= ': ' . $this->determineType($fieldType);
        }

        $this->allContentReplace[self::REPLACE_DEFAULT_WORKFLOW_OUTPUT_TYPE] = $outputType;
    }

    /**
     * @param string[] $fileContent
     */
    private function constructWorkflowContentAllInputData(array $fileContent)
    {
        $allInputDataString = '';

        if (count($this->allInputField) > 0) {
            $allInputDataString .= '
            [';
        } else {
            $allInputDataString .= '[';
        }

        foreach ($this->allInputField as $fieldName => $fieldType) {
            $allInputDataString .= '
                self::FIELD_' . Utility::formatTextToUppercase(Utility::formatTextToSnakeCase($fieldName)) . ' => $' . $fieldName . ',';
        }

        if (count($this->allInputField) > 0) {
            $allInputDataString .= '
            ]
        ';
        } else {
            $allInputDataString .= ']';
        }

        $this->allContentReplace[self::REPLACE_DEFAULT_WORKFLOW_ALL_INPUT_DATA] = $allInputDataString;
    }
}
