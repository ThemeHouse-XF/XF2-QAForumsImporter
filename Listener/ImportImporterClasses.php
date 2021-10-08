<?php

namespace ThemeHouse\QAForumsImporter\Listener;

use XF\Container;
use XF\Import\Manager;
use XF\SubContainer\Import;

/**
 * Class ImportImporterClasses
 * @package ThemeHouse\QAForumsImporter\Listener
 */
class ImportImporterClasses
{
    /**
     * @param Import $container
     * @param Container $parentContainer
     * @param array $importers
     */
    public static function importImporterClasses(Import $container, Container $parentContainer, array &$importers)
    {
        $importers = array_merge(
            $importers, Manager::getImporterShortNamesForType('ThemeHouse/QAForumsImporter')
        );
    }
}
