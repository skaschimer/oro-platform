<?php

namespace Oro\Bundle\OrganizationBundle\Migrations\Schema\v1_4;

use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Psr\Log\LoggerInterface;

class DropUnusedEntityConfigFieldValuesQuery extends ParametrizedMigrationQuery
{
    #[\Override]
    public function getDescription()
    {
        $logger = new ArrayLogger();
        $logger->info('Drop unused extend config field values');
        $this->doExecute($logger, true);

        return $logger->getMessages();
    }

    #[\Override]
    public function execute(LoggerInterface $logger)
    {
        $this->doExecute($logger);
    }

    public function doExecute(LoggerInterface $logger, $dryRun = false)
    {
        $indexSql = <<<DQL
             DELETE FROM oro_entity_config_index_value WHERE
             field_id IN (SELECT id FROM oro_entity_config_field
                    WHERE entity_id = (SELECT id FROM oro_entity_config WHERE class_name = ? LIMIT 1) AND
                    field_name IN (?, ?)) AND
             entity_id IS NULL;
DQL;
        $indexParams = [
            'Oro\\Bundle\\OrganizationBundle\\Entity\\Organization',
            'currency',
            'precision',

        ];
        $fieldsSql = <<<DQL
             DELETE FROM oro_entity_config_field WHERE field_name IN (?, ?)
             AND entity_id IN (SELECT id FROM oro_entity_config WHERE class_name = ?);
DQL;
        $fieldParams = [
            'currency',
            'precision',
            'Oro\\Bundle\\OrganizationBundle\\Entity\\Organization',
        ];

        $this->logQuery($logger, $indexSql, $indexParams);
        $this->logQuery($logger, $fieldsSql, $fieldParams);
        if (!$dryRun) {
            $this->connection->executeStatement($indexSql, $indexParams);
            $this->connection->executeStatement($fieldsSql, $fieldParams);
        }
    }
}
