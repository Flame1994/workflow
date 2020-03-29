<?php
namespace App\Workflows\Common;

use Exception;

/**
 * Parent class for all workflows.
 */
abstract class Workflow
{
    /**
     * Error constants.
     */
     const ERROR_DATA_KEY_MISSING = 'Cannot call workflow; key "%s" is missing from workflow data.';

    /**
     * @var mixed
     */
    protected $result;

    /**
     * @param array $data
     */
    public function __construct(array $data)
    {
    }

    /**
     * Start the workflow execution.
     */
    abstract protected function start();

    /**
     * @return mixed
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     */
    protected function executeEnd()
    {
    }

    /**
     * @param array $data
     * @param array $allExpectedKey
     *
     * @throws Exception
     */
    protected function assertDataHasAllKey($data, array $allExpectedKey)
    {
        foreach ($allExpectedKey as $expectedKey) {
            if (!array_key_exists($expectedKey, $data)) {
                throw new Exception(vsprintf(self::ERROR_DATA_KEY_MISSING, [$expectedKey]));
            }
        }
    }
}
