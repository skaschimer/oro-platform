<?php

namespace Oro\Bundle\EntityConfigBundle\Migration;

use Doctrine\DBAL\Types\Types;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Psr\Log\LoggerInterface;

/**
 * Remove config of a given entity.
 */
class RemoveTableQuery extends ParametrizedMigrationQuery
{
    /** @var string  */
    protected $entityClass;

    /**
     * @param string $entityClass
     */
    public function __construct($entityClass)
    {
        $this->entityClass = $entityClass;
    }

    #[\Override]
    public function execute(LoggerInterface $logger)
    {
        $sql = 'SELECT id FROM oro_entity_config WHERE class_name = ? LIMIT 1';

        $fieldRow = $this->connection->fetchAssociative($sql, [$this->entityClass], [Types::STRING]);
        if ($fieldRow) {
            $this->executeQuery($logger, 'DELETE FROM oro_entity_config_field WHERE entity_id = ?', [$fieldRow['id']]);
            $this->executeQuery($logger, 'DELETE FROM oro_entity_config WHERE id = ?', [$fieldRow['id']]);
        }
    }

    /**
     * @param LoggerInterface $logger
     * @param string $sql
     * @param array $parameters
     */
    protected function executeQuery(LoggerInterface $logger, $sql, array $parameters = [])
    {
        $statement = $this->connection->prepare($sql);
        $statement->executeQuery($parameters);
        $this->logQuery($logger, $sql, $parameters);
    }

    #[\Override]
    public function getDescription()
    {
        return 'Remove config of entity' . $this->entityClass;
    }
}
