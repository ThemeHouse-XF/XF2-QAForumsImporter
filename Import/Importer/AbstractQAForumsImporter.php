<?php


namespace ThemeHouse\QAForumsImporter\Import\Importer;


use XF\Import\Importer\AbstractImporter;

/**
 * Class THQAForums
 * @package ThemeHouse\QAForumsImporter\Import\Importer
 */
abstract class AbstractQAForumsImporter extends AbstractImporter
{
    /**
     * @param array $vars
     * @return null
     */
    public function renderBaseConfigOptions(array $vars)
    {
        return null;
    }

    /**
     * @param array $baseConfig
     * @param array $errors
     * @return bool
     */
    public function validateBaseConfig(array &$baseConfig, array &$errors)
    {
        return true;
    }

    /**
     * @param array $vars
     * @return null
     */
    public function renderStepConfigOptions(array $vars)
    {
        return null;
    }

    /**
     * @param array $steps
     * @param array $stepConfig
     * @param array $errors
     * @return bool
     */
    public function validateStepConfig(array $steps, array &$stepConfig, array &$errors)
    {
        return true;
    }

    /**
     * @return false
     */
    public function canRetainIds()
    {
        return false;
    }

    /**
     * @return false
     */
    public function resetDataForRetainIds()
    {
        return false;
    }

    /**
     * @param array $stepsRun
     * @return array
     */
    public function getFinalizeJobs(array $stepsRun)
    {
        return [];
    }

    /**
     * @return array
     */
    protected function getBaseConfigDefault()
    {
        return [];
    }

    /**
     * @return array
     */
    protected function getStepConfigDefault()
    {
        return [];
    }

    /**
     *
     */
    protected function doInitializeSource()
    {
    }
}