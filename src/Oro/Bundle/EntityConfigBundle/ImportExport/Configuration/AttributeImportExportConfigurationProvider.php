<?php

namespace Oro\Bundle\EntityConfigBundle\ImportExport\Configuration;

use Oro\Bundle\EntityConfigBundle\Async\Topic\AttributePreImportTopic;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfiguration;
use Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfigurationInterface;
use Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfigurationProviderInterface;

/**
 * Configuration provider for import/export operations with the attribute.
 */
class AttributeImportExportConfigurationProvider implements ImportExportConfigurationProviderInterface
{
    const ATTRIBUTE_IMPORT_FROM_CSV_JOB_NAME = 'attribute_import_from_csv';
    const ATTRIBUTE_IMPORT_VALIDATION_FROM_CSV_JOB_NAME = 'attribute_import_validation_from_csv';

    #[\Override]
    public function get(): ImportExportConfigurationInterface
    {
        return new ImportExportConfiguration([
            ImportExportConfiguration::FIELD_ENTITY_CLASS => FieldConfigModel::class,
            ImportExportConfiguration::FIELD_IMPORT_JOB_NAME => self::ATTRIBUTE_IMPORT_FROM_CSV_JOB_NAME,
            ImportExportConfiguration::FIELD_IMPORT_VALIDATION_JOB_NAME
                => self::ATTRIBUTE_IMPORT_VALIDATION_FROM_CSV_JOB_NAME,
            ImportExportConfiguration::FIELD_IMPORT_PROCESSOR_ALIAS => 'oro_entity_config_entity_field.add_or_replace',
            ImportExportConfiguration::FIELD_EXPORT_TEMPLATE_JOB_NAME => 'entity_export_template_to_csv',
            ImportExportConfiguration::FIELD_EXPORT_TEMPLATE_PROCESSOR_ALIAS =>
                'oro_entity_config_attribute.export_template',
            ImportExportConfiguration::FIELD_IMPORT_PROCESSOR_TOPIC_NAME => AttributePreImportTopic::getName(),
        ]);
    }
}
