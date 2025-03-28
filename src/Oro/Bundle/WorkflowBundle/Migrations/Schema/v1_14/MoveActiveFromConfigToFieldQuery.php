<?php

namespace Oro\Bundle\WorkflowBundle\Migrations\Schema\v1_14;

use Doctrine\DBAL\Types\Types;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Psr\Log\LoggerInterface;

class MoveActiveFromConfigToFieldQuery extends ParametrizedMigrationQuery
{
    #[\Override]
    public function getDescription()
    {
        $logger = new ArrayLogger();
        $this->migrateConfigs($logger, true);

        return array_merge(
            ['Moves from entities config "active_workflow" to corresponded workflow "active" field.'],
            $logger->getMessages()
        );
    }

    #[\Override]
    public function execute(LoggerInterface $logger)
    {
        $this->migrateConfigs($logger);
    }

    /**
     * @param LoggerInterface $logger
     * @param bool $dryRun
     */
    protected function migrateConfigs(LoggerInterface $logger, $dryRun = false)
    {
        $queries = [];

        // prepare update queries
        $rows = $this->getRows($logger);
        foreach ($rows as $row) {
            $data = $this->connection->convertToPHPValue($row['data'], 'array');

            if ($this->isWorkflowAwareData($data)) {
                $workflowName = $data['workflow']['active_workflow'];
                if (!empty($workflowName)) {
                    $queries[] = [
                        'UPDATE oro_workflow_definition SET active = :is_active WHERE name = :workflow_name',
                        ['is_active' => true, 'workflow_name' => $workflowName],
                        ['is_active' => Types::BOOLEAN, 'workflow_name' => Types::STRING]
                    ];
                }

                unset($data['workflow']['active_workflow']);
                $queries[] = [
                    'UPDATE oro_entity_config SET data = :data WHERE id = :id',
                    ['data' => $data, 'id' => $row['id']],
                    ['data' => Types::ARRAY, 'id' => Types::INTEGER]
                ];
            }
        }

        // execute update queries
        foreach ($queries as $val) {
            $this->logQuery($logger, $val[0], $val[1], $val[2]);
            if (!$dryRun) {
                $this->connection->executeStatement($val[0], $val[1], $val[2]);
            }
        }
    }

    /**
     * @param LoggerInterface $logger
     * @return array
     */
    private function getRows(LoggerInterface $logger)
    {
        $query  = 'SELECT id, data FROM oro_entity_config';

        $this->logQuery($logger, $query);

        return $this->connection->fetchAllAssociative($query);
    }

    /**
     * @param mixed $data
     * @return bool
     */
    private function isWorkflowAwareData($data)
    {
        return is_array($data) &&
            array_key_exists('workflow', $data) &&
            array_key_exists('active_workflow', $data['workflow']);
    }
}
