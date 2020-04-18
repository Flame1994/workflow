<?php
namespace Rhaarhoff\Workflow\Generator;

use Rhaarhoff\Workflow\Helpers\Utility;

class ConstructWorkflowBase
{
    /**
     * Workflow file replace constants.
     */
    const REPLACE_DEFAULT_WORKFLOW_NAMESPACE = 'DefaultWorkflowNameSpace';
    const REPLACE_DEFAULT_WORKFLOW_ALL_IMPORT = 'DefaultWorkflowAllImport';
    const REPLACE_DEFAULT_WORKFLOW_NAME_BASE = 'DefaultWorkflowNameBase';
    const REPLACE_DEFAULT_WORKFLOW_ALL_FIELD_CONSTANT = 'DefaultWorkflowAllFieldConstant';
    const REPLACE_DEFAULT_WORKFLOW_ALL_FIELD_VARIABLE = 'DefaultWorkflowAllFieldVariable';
    const REPLACE_DEFAULT_WORKFLOW_ALL_INPUT_PARAMETER_ARRAY = 'DefaultWorkflowAllInputParameterArray';
    const REPLACE_DEFAULT_WORKFLOW_INPUT_SET_ALL_PROPERTY = 'DefaultWorkflowInputSetAllProperty';
    const REPLACE_DEFAULT_WORKFLOW_EXECUTE_START_STATE = 'DefaultWorkflowExecuteStartState';
    const REPLACE_DEFAULT_WORKFLOW_STATE_ALL_ABSTRACT_FUNCTION = 'DefaultWorkflowStateAllAbstractFunction';
    const REPLACE_DEFAULT_WORKFLOW_STATE_ALL_FUNCTION = 'DefaultWorkflowStateAllFunction';
    const REPLACE_DEFAULT_WORKFLOW_OUTPUT_NAME = 'DefaultWorkflowOutputName';

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
        self::REPLACE_DEFAULT_WORKFLOW_NAMESPACE => '',
        self::REPLACE_DEFAULT_WORKFLOW_ALL_IMPORT => '',
        self::REPLACE_DEFAULT_WORKFLOW_NAME_BASE => '',
        self::REPLACE_DEFAULT_WORKFLOW_ALL_FIELD_CONSTANT => '',
        self::REPLACE_DEFAULT_WORKFLOW_ALL_FIELD_VARIABLE => '',
        self::REPLACE_DEFAULT_WORKFLOW_ALL_INPUT_PARAMETER_ARRAY => '',
        self::REPLACE_DEFAULT_WORKFLOW_INPUT_SET_ALL_PROPERTY => '',
        self::REPLACE_DEFAULT_WORKFLOW_EXECUTE_START_STATE => '',
        self::REPLACE_DEFAULT_WORKFLOW_STATE_ALL_ABSTRACT_FUNCTION => '',
        self::REPLACE_DEFAULT_WORKFLOW_STATE_ALL_FUNCTION => '',
        self::REPLACE_DEFAULT_WORKFLOW_OUTPUT_NAME => '',
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
        $this->constructWorkflowContentAllFieldConstant($fileContent);
        $this->constructWorkflowContentAllFieldVariable($fileContent);
        $this->constructWorkflowContentAllInputParameterArray($fileContent);
        $this->constructWorkflowContentInputSetAllProperty($fileContent);
        $this->constructWorkflowContentExecuteStartState($fileContent);
        $this->constructWorkflowContentStateAllAbstractFunction($fileContent);
        $this->constructWorkflowContentStateAllFunction($fileContent);
        $this->constructWorkflowContentOutputName($fileContent);
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
        $nameSpace = $fileContent[self::DEFINITION_FILE_FIELD_NAMESPACE] . '\\Generated';

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

        $this->allContentReplace[self::REPLACE_DEFAULT_WORKFLOW_ALL_IMPORT] = $allImportString;
    }

    /**
     * @param string[] $fileContent
     */
    private function constructWorkflowContentName(array $fileContent)
    {
        $name = $fileContent[self::DEFINITION_FILE_FIELD_NAME];

        $this->allContentReplace[self::REPLACE_DEFAULT_WORKFLOW_NAME_BASE] = 'Workflow' . $name . 'Base';
    }

    /**
     * @param string[] $fileContent
     */
    private function constructWorkflowContentAllFieldConstant(array $fileContent)
    {
        $allFieldString = '';

        foreach ($this->allField as $fieldName => $fieldType) {
            $allFieldString .= '    const FIELD_' . Utility::formatTextToUppercase(Utility::formatTextToSnakeCase($fieldName)) . ' = \'' . $fieldName . '\';' . PHP_EOL;
        }

        $this->allContentReplace[self::REPLACE_DEFAULT_WORKFLOW_ALL_FIELD_CONSTANT] = $allFieldString;
    }

    /**
     * @param string[] $fileContent
     */
    private function constructWorkflowContentAllFieldVariable(array $fileContent)
    {
        $allFieldVariableString = '';

        foreach ($this->allField as $fieldName => $fieldType) {
            $allFieldVariableString .= '    /**
    * @var ' . $fieldType . '
    */
    private $' . $fieldName . ';' . PHP_EOL . PHP_EOL;
        }

        $this->allContentReplace[self::REPLACE_DEFAULT_WORKFLOW_ALL_FIELD_VARIABLE] = $allFieldVariableString;
    }

    /**
     * @param string[] $fileContent
     */
    private function constructWorkflowContentAllInputParameterArray(array $fileContent)
    {
        $allInputString = '[' . PHP_EOL;

        foreach ($this->allInputField as $fieldName => $fieldType) {
            $allInputString .= '                \'' . $fieldName . '\',' . PHP_EOL;
        }

        $allInputString .= '            ]';

        $this->allContentReplace[self::REPLACE_DEFAULT_WORKFLOW_ALL_INPUT_PARAMETER_ARRAY] = $allInputString;
    }

    /**
     * @param string[] $fileContent
     */
    private function constructWorkflowContentInputSetAllProperty(array $fileContent)
    {
        $allPropertyString = '';

        foreach ($this->allInputField as $fieldName => $fieldType) {
            $allPropertyString .= '
        if (array_key_exists(\'' . $fieldName . '\', $data)) {
            $this->' . $fieldName . ' = $data[\'' . $fieldName .'\'];
        }' . PHP_EOL;
        }

        $this->allContentReplace[self::REPLACE_DEFAULT_WORKFLOW_INPUT_SET_ALL_PROPERTY] = $allPropertyString;
    }

    /**
     * @param string[] $fileContent
     */
    private function constructWorkflowContentExecuteStartState(array $fileContent)
    {
        $startState = $fileContent[self::DEFINITION_FILE_FIELD_START_STATE];

        $startStateString = 'try {
            $this->execute' . $startState . '();
        } catch(Exception $exception) {
            throw $exception;
        }';

        $this->allContentReplace[self::REPLACE_DEFAULT_WORKFLOW_EXECUTE_START_STATE] = $startStateString;
    }

    /**
     * @param string[] $fileContent
     */
    private function constructWorkflowContentStateAllAbstractFunction(array $fileContent)
    {
        $allWorkflowStep = $fileContent[self::DEFINITION_FILE_FIELD_WORKFLOW];

        $allFunctionAbstractString = '';

        foreach ($allWorkflowStep as $workflowStepName => $workflowStepContent) {
            $allWorkflowStepInput = $workflowStepContent[self::WORKFLOW_STATE_FIELD_PARAMETERS];
            $allWorkflowStepResult = $workflowStepContent[self::WORKFLOW_STATE_FIELD_RESULT];

            $allFunctionAbstractString .= '
    /**';
            foreach ($allWorkflowStepInput as $inputName => $inputType) {
                $allFunctionAbstractString .= '
     * @param ' . $inputType . ' $' . $inputName;
            }

            $allFunctionAbstractString .= '
     *
     * @return ' . $allWorkflowStepResult[array_key_first($allWorkflowStepResult)] . '
     */
    abstract protected function ' . lcfirst($workflowStepName) . '(';
            foreach ($allWorkflowStepInput as $inputName => $inputType) {
                if ($this->hasTypeNull($inputType)) {
                    $allFunctionAbstractString .= '
        ' . $this->determineInputType(explode('|', $inputType)[0]) . ' $' . $inputName . ' = null';
                } else {
                    $allFunctionAbstractString .= '
        ' . $this->determineInputType($inputType) . ' $' . $inputName;
                }
                if (array_key_last($allWorkflowStepInput) !== $inputName) {
                    $allFunctionAbstractString .= ',';
                }
            }
            $allFunctionAbstractString .= '
    );' . PHP_EOL;
        }

        $this->allContentReplace[self::REPLACE_DEFAULT_WORKFLOW_STATE_ALL_ABSTRACT_FUNCTION] = $allFunctionAbstractString;
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
    private function determineInputType(string $inputType): string
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
    private function constructWorkflowContentStateAllFunction(array $fileContent)
    {
        $allWorkflowStep = $fileContent[self::DEFINITION_FILE_FIELD_WORKFLOW];

        $allFunctionString = '';

        foreach ($allWorkflowStep as $workflowStepName => $workflowStepContent) {
            $allWorkflowStepInput = $workflowStepContent[self::WORKFLOW_STATE_FIELD_PARAMETERS];
            $allWorkflowStepResult = $workflowStepContent[self::WORKFLOW_STATE_FIELD_RESULT];
            $allWorkflowStepTransition = $workflowStepContent[self::WORKFLOW_STATE_FIELD_TRANSITION];

            $allFunctionString .= '
    /**
     */
    private function execute' . $workflowStepName . '()
    {
        try {';
            foreach ($allWorkflowStepResult as $resultName => $resultType) {
                $allFunctionString .= '
            $this->' . $resultName . ' = $this->' . lcfirst($workflowStepName)  . '(';

                foreach ($allWorkflowStepInput as $inputName => $inputType) {
                    $allFunctionString .= '
                $this->' . $inputName;

                    if ($inputName !== array_key_last($allWorkflowStepInput)) {
                        $allFunctionString .= ',';
                    }
                }

                $allFunctionString .= '
            );';
            }

            $allFunctionString .= '
        } catch(Exception $exception) {
            throw $exception;
        }' . PHP_EOL;

            $hasParsedFirstTransition = false;

            foreach ($allWorkflowStepTransition as $transitionName => $transitionRequirement) {
                if (count($allWorkflowStepTransition) === 1) {
                    $allFunctionString .= '
        $this->execute' . $transitionName .'();';
                } else {
                    if ($hasParsedFirstTransition === false) {
                        $allFunctionString .= '
        if (' . $transitionRequirement . ') {
            $this->execute' . $transitionName .'();
        }';
                        $hasParsedFirstTransition = true;
                    } else {
                        $allFunctionString .= ' elseif (' . $transitionRequirement . ') {
            $this->execute' . $transitionName .'();
        }';
                    }
                }
            }

            if (count($allWorkflowStepTransition) > 1) {
                $allFunctionString .= ' else {
            throw new Exception();
        }';
            }

            $allFunctionString .= '
    }' . PHP_EOL;
        }

        $this->allContentReplace[self::REPLACE_DEFAULT_WORKFLOW_STATE_ALL_FUNCTION] = $allFunctionString;
    }

    /**
     * @param string[] $fileContent
     */
    private function constructWorkflowContentOutputName(array $fileContent)
    {
        $outputNameString = array_key_first($fileContent[self::DEFINITION_FILE_FIELD_OUTPUT]);

        $this->allContentReplace[self::REPLACE_DEFAULT_WORKFLOW_OUTPUT_NAME] = $outputNameString . ';';
    }
}
