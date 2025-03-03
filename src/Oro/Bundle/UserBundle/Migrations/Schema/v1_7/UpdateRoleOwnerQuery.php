<?php

namespace Oro\Bundle\UserBundle\Migrations\Schema\v1_7;

use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Psr\Log\LoggerInterface;

class UpdateRoleOwnerQuery extends ParametrizedMigrationQuery
{
    #[\Override]
    public function getDescription()
    {
        $logger = new ArrayLogger();
        $this->doExecute($logger, true);

        return $logger->getMessages();
    }

    #[\Override]
    public function execute(LoggerInterface $logger)
    {
        $this->doExecute($logger);
    }

    /**
     * @param LoggerInterface $logger
     * @param bool            $dryRun
     */
    public function doExecute(LoggerInterface $logger, $dryRun = false)
    {
        $className = 'Oro\Bundle\UserBundle\Entity\Role';
        $classConfig = $this->loadEntityConfigData($logger, $className);
        $data = $this->connection->convertToPHPValue($classConfig['data'], 'array');

        unset($data['ownership']);

        $query  = 'UPDATE oro_entity_config SET data = :data WHERE id = :id';
        $params = ['data' => $data, 'id' => $classConfig['id']];
        $types  = ['data' => 'array', 'id' => 'integer'];
        $this->logQuery($logger, $query, $params, $types);
        if (!$dryRun) {
            $this->connection->executeStatement($query, $params, $types);
        }
    }

    /**
     * @param LoggerInterface $logger
     * @param string          $className
     *
     * @return array
     */
    protected function loadEntityConfigData(LoggerInterface $logger, $className)
    {
        $sql = 'SELECT ec.id, ec.data'
            . ' FROM oro_entity_config ec'
            . ' WHERE ec.class_name = :class';
        $params = ['class' => $className];
        $types  = ['class' => 'string'];
        $this->logQuery($logger, $sql, $params, $types);

        $rows = $this->connection->fetchAllAssociative($sql, $params, $types);

        return $rows[0];
    }
}
