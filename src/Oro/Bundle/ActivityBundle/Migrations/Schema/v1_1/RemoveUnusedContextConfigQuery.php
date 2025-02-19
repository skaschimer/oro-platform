<?php

namespace Oro\Bundle\ActivityBundle\Migrations\Schema\v1_1;

use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Psr\Log\LoggerInterface;

class RemoveUnusedContextConfigQuery extends ParametrizedMigrationQuery
{
    #[\Override]
    public function getDescription()
    {
        $logger = new ArrayLogger();
        $this->removeContextConfigs($logger, true);

        return $logger->getMessages();
    }

    #[\Override]
    public function execute(LoggerInterface $logger)
    {
        $this->removeContextConfigs($logger);
    }

    /**
     * @param LoggerInterface $logger
     * @param bool            $dryRun
     */
    protected function removeContextConfigs(LoggerInterface $logger, $dryRun = false)
    {
        $configs = $this->loadConfigs($logger);
        foreach ($configs as $id => $data) {
            if (!isset($data['entity']['context-grid'])) {
                continue;
            }
            unset($data['entity']['context-grid']);

            $query  = 'UPDATE oro_entity_config SET data = :data WHERE id = :id';
            $params = ['data' => $data, 'id' => $id];
            $types  = ['data' => 'array', 'id' => 'integer'];
            $this->logQuery($logger, $query, $params, $types);
            if (!$dryRun) {
                $this->connection->executeStatement($query, $params, $types);
            }
        }
    }

    /**
     * @param LoggerInterface $logger
     *
     * @return array key = {config id}, value = data
     */
    protected function loadConfigs(LoggerInterface $logger)
    {
        $sql = 'SELECT id, data FROM oro_entity_config';
        $this->logQuery($logger, $sql);

        $result = [];

        $rows = $this->connection->fetchAllAssociative($sql);
        foreach ($rows as $row) {
            $result[$row['id']] = $this->connection->convertToPHPValue($row['data'], 'array');
        }

        return $result;
    }
}
