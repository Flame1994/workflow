<?php
namespace Rhaarhoff\Workflow\Validator;

use Exception;
use Rhaarhoff\Workflow\Helpers\Utility;

class ValidateWorkflow
{
    /**
     * Error constants.
     */
    const ERROR_INVALID_DEFINITION_FILE = 'Error - Invalid definition name: "%s"';
    const ERROR_CLASS_NOT_EXISTS = 'Error - Class does not exist for import: "%s"';
    const ERROR_NO_INPUT_PARAMETER = 'Error - No input parameters specified.';
    const ERROR_ONE_OUTPUT_PARAMETER = 'Error - There should be one output field set. You have specified %d.';
    const ERROR_INVALID_INPUT_FIELD_NAME =
        'Error - Invalid input field name: "%s". Should start with a lowercase letter and must be alphanumeric.';
    const ERROR_INVALID_OUTPUT_FIELD_NAME =
        'Error - Invalid output field name: "%s". Should start with a lowercase letter and must be alphanumeric.';
    const ERROR_INVALID_CLASS_NAME =
        'Error - Invalid class name: "%s". Should start with an uppercase letter and must be alphanumeric.';
    const ERROR_INVALID_START_STATE_NAME =
        'Error - Invalid start state name: "%s". Should start with an uppercase letter and must be alphanumeric.';
    const ERROR_IMPORT_MISSING = 'Error - Import is missing for parameter: "%s" of type "%s".';
    const ERROR_WORKFLOW_STATE_NOT_REACHABLE = 'Error - Workflow state "%s" is not reachable.';
    const ERROR_NO_TRANSITION_SPECIFIED = 'Error - No transitions specified for workflow step "%s".';
    const ERROR_CIRCULAR_TRANSITION_REFERENCE =
        'Error - Circular reference in workflow transition. Can\'t transition from "%s" to "%s"';
    const ERROR_WORKFLOW_STATE_NOT_SPECIFIED =
        'Error - Transition to "%s" not possible, since workflow state is not specified.';
    const ERROR_START_STATE_DOES_NOT_MATCH =
        'Error - Start state "%s" specified in workflow does not match defined start state "%s".';
    const ERROR_NO_WORKFLOW_STATES_SPECIFIED = 'Error - No workflow states defined.';
    const ERROR_PARAMETER_DECLARED_MULTIPLE_TYPE = 'Error - Parameter "%s" has been declared with type "%s" and "%s".';
    const ERROR_PARAMETER_NOT_PREVIOUSLY_DECLARED = 'Error - Parameter "%s" has not been previously declared.';
    const ERROR_WORKFLOW_STATE_REQUIRES_FIELD = 'Error - Workflow state "%s" requires the "%s" field.';
    const ERROR_WORKFLOW_STATE_ONE_RESULT = 'Error - Workflow state "%s" needs one result field set.';
    const ERROR_NO_REQUIREMENT_SPECIFIED = 'Transition function "%s" has no requirement specified.';

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
    const REGEX_ALPHANUMERIC_START_UPPERCASE = '/^[A-Z][a-zA-Z0-9]*(\[\]){0,1}$/';
    const REGEX_ALPHANUMERIC_START_LOWERCASE = '/^[a-z][a-zA-Z0-9]*(\[\]){0,1}$/';

    /**
     * @var $allError
     */
    protected $allError = [];

    /**
     * @param string[] $fileContent
     * @param string $fullFilePath
     */
    public function __construct(array $fileContent, string $fullFilePath)
    {
        try {
            $this->assertDefinitionFileValid($fileContent, $fullFilePath);
        } catch (Exception $e) {
            // Do nothing
        }
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
    public function getError()
    {
        return $this->allError;
    }

    /**
     * @param string[] $fileContent
     * @param string $fullFilePath
     */
    public function assertDefinitionFileValid(array $fileContent, string $fullFilePath)
    {
        $this->assertHasAllRequiredField($fileContent, $fullFilePath);

        foreach (self::DEFINITION_FILE_REQUIRED_FIELDS as $field) {
            $this->assertFieldContentValid($fileContent, $fileContent[$field], $field);
        }
    }

    /**
     * @param string[] $fileContent
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
     * @param string[] $fileContent
     * @param string[]|string $fieldContent
     * @param string $field
     */
    private function assertFieldContentValid(array $fileContent, $fieldContent, string $field)
    {
        switch ($field) {
            case self::DEFINITION_FILE_FIELD_NAME:
                $this->assertFieldNameValid($fieldContent);
                break;
            case self::DEFINITION_FILE_FIELD_USES:
                $this->assertAllImportClassExists($fieldContent);
                break;
            case self::DEFINITION_FILE_FIELD_NAMESPACE:
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
                $this->assertAllWorkflowStateValid($fileContent, $fieldContent);
                break;
        }
    }

    /**
     * @param string $name
     */
    private function assertFieldNameValid(string $name)
    {
        if (preg_match(self::REGEX_ALPHANUMERIC_START_UPPERCASE, $name)) {
            // Do nothing
        } else {
            $this->error(vsprintf(
                self::ERROR_INVALID_DEFINITION_FILE,
                [
                    $name,
                ]
            ));
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
                $this->error(vsprintf(
                    self::ERROR_CLASS_NOT_EXISTS,
                    [
                        $import,
                    ]
                ));
            }
        }
    }

    /**
     * @param string[] $allInputField
     */
    private function assertInputHasFieldSet(array $allInputField)
    {
        if (Utility::countNumberOfElementInArray($allInputField) === 0) {
            $this->error(self::ERROR_NO_INPUT_PARAMETER);
        }
    }

    /**
     * @param string[] $fileContent
     * @param string[] $allInputField
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
            $this->error(vsprintf(
                self::ERROR_ONE_OUTPUT_PARAMETER,
                [
                    Utility::countNumberOfElementInArray($allOutputField),
                ]
            ));
        }
    }

    /**
     * @param string[] $fileContent
     * @param string[] $allOutputField
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
        if (preg_match(self::REGEX_ALPHANUMERIC_START_LOWERCASE, $fieldName)) {
            // Do nothing
        } else {
            $this->error(vsprintf(
                self::ERROR_INVALID_INPUT_FIELD_NAME,
                [
                    $fieldName,
                ]
            ));
        }
    }

    /**
     * @param string $fieldName
     */
    private function assertOutputFieldNameValid(string $fieldName)
    {
        if (preg_match(self::REGEX_ALPHANUMERIC_START_LOWERCASE, $fieldName)) {
            // Do nothing
        } else {
            $this->error(vsprintf(
                self::ERROR_INVALID_OUTPUT_FIELD_NAME,
                [
                    $fieldName,
                ]
            ));
        }
    }

    /**
     * @param string $fieldClass
     */
    private function assertFieldClassNameValid(string $fieldClass)
    {
        if (preg_match(self::REGEX_ALPHANUMERIC_START_UPPERCASE, $fieldClass)) {
            // Do nothing
        } else {
            $this->error(vsprintf(
                self::ERROR_INVALID_CLASS_NAME,
                [
                    $fieldClass,
                ]
            ));
        }
    }

    /**
     * @param string $startState
     */
    private function assertStartStateValid(string $startState)
    {
        if (preg_match(self::REGEX_ALPHANUMERIC_START_UPPERCASE, $startState)) {
            // Do nothing
        } else {
            $this->error(vsprintf(
                self::ERROR_INVALID_START_STATE_NAME,
                [
                    $startState,
                ]
            ));
        }
    }

    /**
     * @param string[] $allImport
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

            if ($lastImportPart === str_replace('[]', '', $inputFieldClass)) {
                return true;
            }
        }

        $this->error(vsprintf(
            self::ERROR_IMPORT_MISSING,
            [
                $inputFieldName,
                $inputFieldClass,
            ]
        ));

        return false;
    }

    /**
     * @param string[] $fileContent
     * @param string[] $allWorkflowState
     */
    private function assertAllWorkflowStateValid(array $fileContent, array $allWorkflowState)
    {
        $startStateName = $fileContent[self::DEFINITION_FILE_FIELD_START_STATE];

        $this->assertWorkflowHasStateSet($allWorkflowState);
        $this->assertWorkflowStartStateValid(
            $startStateName,
            $allWorkflowState
        );

        $allPossibleInput = [$fileContent[self::DEFINITION_FILE_FIELD_INPUT]];

        foreach ($allWorkflowState as $workflowName => $workflowState) {
            $this->assertAllWorfklowStateParameterValid($workflowName, $workflowState, $allPossibleInput);
            $this->assertWorkflowStateResultValid($fileContent, $workflowName, $workflowState, $allPossibleInput);

            $allPossibleInput[] = $workflowState[self::WORKFLOW_STATE_FIELD_RESULT];

            $this->assertWorkflowStateHasFieldTransition($workflowName, $workflowState);
        }

        $allWorkflowStep = [$startStateName];
        $workflowStartState = $allWorkflowState[$startStateName];

        $this->assertWorkflowAllTransitionValid($allWorkflowState, $allWorkflowStep, $workflowStartState);
        $this->assertWorkflowAllTransitionReachable($allWorkflowState, $startStateName);
    }

    /**
     * @param string[] $allWorkflowState
     * @param string $workflowStartState
     */
    private function assertWorkflowAllTransitionReachable(array $allWorkflowState, string $workflowStartState)
    {
        $allWorkflowStateName = [];

        foreach ($allWorkflowState as $workflowName => $workflowState) {
            $allWorkflowStateName[] = $workflowName;
        }

        foreach ($allWorkflowStateName as $workflowStateName) {
            $isWorkflowStateReachable = false;

            foreach ($allWorkflowState as $workflowState) {
                $allWorkflowStateTransition = $workflowState[self::WORKFLOW_STATE_FIELD_TRANSITION];

                foreach ($allWorkflowStateTransition as $transitionName => $transitionRequirement) {
                    if ($transitionName === $workflowStateName) {
                        $isWorkflowStateReachable = true;
                    }
                }
            }

            if ($workflowStateName === $workflowStartState) {
                $isWorkflowStateReachable = true;
            }

            if ($isWorkflowStateReachable) {
                // Do nothing
            } else {
                $this->error(vsprintf(
                    self::ERROR_WORKFLOW_STATE_NOT_REACHABLE,
                    [
                        $workflowStateName,
                    ]
                ));
            }
        }
    }

    /**
     * @param string[] $allWorkflowState
     * @param string[] $allWorkflowStep
     * @param string[] $workflowState
     */
    private function assertWorkflowAllTransitionValid(
        array $allWorkflowState,
        array $allWorkflowStep,
        array $workflowState
    ) {
        if (Utility::countNumberOfElementInArray($workflowState[self::WORKFLOW_STATE_FIELD_TRANSITION]) === 0) {
            $this->error(vsprintf(
                self::ERROR_NO_TRANSITION_SPECIFIED,
                [
                    end($allWorkflowStep),
                ]
            ));
        }

        foreach ($workflowState[self::WORKFLOW_STATE_FIELD_TRANSITION] as $transitionName => $transitionRequirement) {
            if (count($workflowState[self::WORKFLOW_STATE_FIELD_TRANSITION]) > 1) {
                if (strlen($transitionRequirement) === 0) {
                    $this->error(vsprintf(
                        self::ERROR_NO_REQUIREMENT_SPECIFIED,
                        [
                            $transitionName,
                        ]
                    ));
                }
            }
            if ($transitionName === self::WORKFLOW_TRANSITION_END) {
                return;
            } else {
                $this->assertWorkflowStateTransitionClassValid($allWorkflowState, $transitionName);

                if (in_array($transitionName, $allWorkflowStep)) {
                    $this->error(vsprintf(
                        self::ERROR_CIRCULAR_TRANSITION_REFERENCE,
                        [
                            end($allWorkflowStep),
                            $transitionName,
                        ]
                    ));
                } else {
                    $allWorkflowStep[] = $transitionName;
                    $newWorkflowState = $allWorkflowState[$transitionName];

                    $this->assertWorkflowAllTransitionValid($allWorkflowState, $allWorkflowStep, $newWorkflowState);
                }
            }
        }
    }

    /**
     * @param string[] $allWorkflowState
     * @param string $transitionName
     */
    private function assertWorkflowStateTransitionClassValid(array $allWorkflowState, string $transitionName)
    {
        foreach ($allWorkflowState as $workflowName => $workflowState) {
            if ($workflowName === $transitionName) {
                return;
            }
        }

        $this->error(vsprintf(
            self::ERROR_WORKFLOW_STATE_NOT_SPECIFIED,
            [
                $transitionName
            ]
        ));
    }

    /**
     * @param string $startState
     * @param string[] $allWorkflowState
     */
    private function assertWorkflowStartStateValid(string $startState, array $allWorkflowState)
    {
        if ($startState === array_key_first($allWorkflowState)) {
            // Do nothing
        } else {
            $this->error(vsprintf(
                self::ERROR_START_STATE_DOES_NOT_MATCH,
                [
                    array_key_first($allWorkflowState),
                    $startState
                ]
            ));
        }
    }

    /**
     * @param string[] $allWorkflowState
     */
    private function assertWorkflowHasStateSet(array $allWorkflowState)
    {
        if (Utility::countNumberOfElementInArray($allWorkflowState) === 0) {
            $this->error(self::ERROR_NO_WORKFLOW_STATES_SPECIFIED);
        }
    }

    /**
     * @param string $workflowName
     * @param string[] $workflowState
     * @param string[] $allPossibleParameter
     */
    private function assertAllWorfklowStateParameterValid(
        string $workflowName,
        array $workflowState,
        array $allPossibleParameter
    ) {
        $this->assertWorkflowStateHasFieldParameter($workflowName, $workflowState);

        foreach ($workflowState[self::WORKFLOW_STATE_FIELD_PARAMETERS] as $parameterName => $parameterType) {
            $this->assertParameterIsPossible($parameterName, $parameterType, $allPossibleParameter);
        }
    }

    /**
     * @param string[] $fileContent
     * @param string $workflowName
     * @param string[] $workflowState
     * @param string[] $allPossibleParameter
     */
    private function assertWorkflowStateResultValid(
        array $fileContent,
        string $workflowName,
        array $workflowState,
        array $allPossibleParameter
    ) {
        $this->assertWorkflowStateHasFieldResult($workflowName, $workflowState);
        $this->assertWorkflowStateHasOnlyOneResult($workflowName, $workflowState);

        foreach ($workflowState[self::WORKFLOW_STATE_FIELD_RESULT] as $resultName => $resultType) {
            foreach ($allPossibleParameter as $possibleParameter) {
                if (isset($possibleParameter[$resultName])) {
                    $possibleParameterType = $possibleParameter[$resultName];

                    if ($resultType === $possibleParameterType) {
                        // Do nothing
                    } else {
                        $this->error(vsprintf(
                            self::ERROR_PARAMETER_DECLARED_MULTIPLE_TYPE,
                            [
                                $resultName,
                                $resultType,
                                $possibleParameterType,
                            ]
                        ));
                    }

                    break;
                }
            }

            $this->assertFieldClassImportSpecified(
                $fileContent[self::DEFINITION_FILE_FIELD_USES],
                $resultName,
                $resultType
            );
        }
    }

    /**
     * @param string $parameterName
     * @param string $parameterType
     * @param string[] $allPossibleParameter
     */
    private function assertParameterIsPossible(
        string $parameterName,
        string $parameterType,
        array $allPossibleParameter
    ) {
        $parameterExists = false;

        foreach ($allPossibleParameter as $possibleParameter) {
            if (isset($possibleParameter[$parameterName])) {
                $possibleParameterType = $possibleParameter[$parameterName];

                if ($parameterType === $possibleParameterType) {
                    // Do nothing
                } else {
                    $this->error(vsprintf(
                        self::ERROR_PARAMETER_DECLARED_MULTIPLE_TYPE,
                        [
                            $parameterName,
                            $parameterType,
                            $possibleParameterType,
                        ]
                    ));
                }

                $parameterExists = true;

                break;
            }
        }

        if ($parameterExists) {
            // Do nothing
        } else {
            $this->error(vsprintf(
                self::ERROR_PARAMETER_NOT_PREVIOUSLY_DECLARED,
                [
                    $parameterName,
                ]
            ));
        }
    }

    /**
     * @param string $workflowName
     * @param string[] $workflowState
     */
    private function assertWorkflowStateHasFieldParameter(string $workflowName, array $workflowState)
    {
        if (isset($workflowState[self::WORKFLOW_STATE_FIELD_PARAMETERS])) {
            // Do nothing
        } else {
            $this->error(vsprintf(
                self::ERROR_WORKFLOW_STATE_REQUIRES_FIELD,
                [
                    $workflowName,
                    self::WORKFLOW_STATE_FIELD_PARAMETERS,
                ]
            ));
        }
    }

    /**
     * @param string $WorkflowName
     * @param string[] $workflowState
     */
    private function assertWorkflowStateHasFieldResult(string $workflowName, array $workflowState)
    {
        if (isset($workflowState[self::WORKFLOW_STATE_FIELD_RESULT])) {
            // Do nothing
        } else {
            $this->error(vsprintf(
                self::ERROR_WORKFLOW_STATE_REQUIRES_FIELD,
                [
                    $workflowName,
                    self::WORKFLOW_STATE_FIELD_RESULT,
                ]
            ));
        }
    }

    /**
     * @param string $workflowName
     * @param string[] $workflowState
     */
    private function assertWorkflowStateHasOnlyOneResult(string $workflowName, array $workflowState)
    {
        if (Utility::countNumberOfElementInArray($workflowState['result']) === 1) {
            // Do nothing
        } else {
            $this->error(vsprintf(
                self::ERROR_WORKFLOW_STATE_ONE_RESULT,
                [
                    $workflowName,
                ]
            ));
        }
    }

    /**
     * @param string $workflowName
     * @param string[] $workflowState
     */
    private function assertWorkflowStateHasFieldTransition(string $workflowName, array $workflowState)
    {
        if (isset($workflowState[self::WORKFLOW_STATE_FIELD_TRANSITION])) {
            // Do nothing
        } else {
            $this->error(vsprintf(
                self::ERROR_WORKFLOW_STATE_REQUIRES_FIELD,
                [
                    $workflowName,
                    self::WORKFLOW_STATE_FIELD_TRANSITION,
                ]
            ));
        }
    }
}
