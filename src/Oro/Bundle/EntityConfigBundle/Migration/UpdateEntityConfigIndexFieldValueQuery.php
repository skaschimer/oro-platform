<?php

namespace Oro\Bundle\EntityConfigBundle\Migration;

use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ConnectionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\ConnectionAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\MigrationQuery;
use Psr\Log\LoggerInterface;

/**
 * Updates entity config index field value.
 */
class UpdateEntityConfigIndexFieldValueQuery implements MigrationQuery, ConnectionAwareInterface
{
    use ConnectionAwareTrait;

    /**
     * @var string
     */
    protected $entityName;

    /**
     * @var string
     */
    protected $fieldName;

    /**
     * @var string
     */
    protected $scope;

    /**
     * @var string
     */
    protected $code;

    /**
     * @var string
     */
    protected $value;

    /**
     * @var null|string
     */
    protected $replaceValue;

    /**
     * @param string $entityName
     * @param string $fieldName
     * @param string $scope
     * @param string $code
     * @param string $value
     * @param string $replaceValue if passed, updating will not happen if existing value !== replaceValue
     */
    public function __construct($entityName, $fieldName, $scope, $code, $value, $replaceValue = null)
    {
        $this->entityName   = $entityName;
        $this->fieldName    = $fieldName;
        $this->scope        = $scope;
        $this->code         = $code;
        $this->value        = $value;
        $this->replaceValue = $replaceValue;
    }

    #[\Override]
    public function getDescription()
    {
        $logger = new ArrayLogger();

        $this->updateEntityConfigIndexValue($logger, true);
        $logger->info(
            sprintf(
                'Set config index value "%s" for field "%s" and entity "%s" in scope "%s" to "%s"',
                $this->code,
                $this->fieldName,
                $this->entityName,
                $this->scope,
                $this->value
            )
        );

        return $logger->getMessages();
    }

    #[\Override]
    public function execute(LoggerInterface $logger)
    {
        $this->updateEntityConfigIndexValue($logger);
    }

    /**
     * @param LoggerInterface $logger
     * @param bool            $dryRun
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function updateEntityConfigIndexValue(LoggerInterface $logger, $dryRun = false)
    {
        $sql        =
            "UPDATE oro_entity_config_index_value
            SET value = ?
            WHERE
                field_id = (SELECT id FROM oro_entity_config_field
                    WHERE entity_id = (SELECT id FROM oro_entity_config WHERE class_name = ? LIMIT 1) AND
                    field_name = ? LIMIT 1) AND
                entity_id IS NULL AND
                scope = ? AND
                code = ?
            ";
        $parameters = [$this->value, $this->entityName, $this->fieldName, $this->scope, $this->code];

        if ($this->replaceValue !== null) {
            $sql .= " AND value = ?";
            $parameters[] = $this->replaceValue;
        }
        $this->logQuery($logger, $sql, $parameters);

        if (!$dryRun) {
            $statement = $this->connection->prepare($sql);
            $statement->executeQuery($parameters);
        }
    }

    /**
     * @param LoggerInterface $logger
     * @param string          $sql
     * @param array           $parameters
     */
    protected function logQuery(LoggerInterface $logger, $sql, array $parameters)
    {
        $message = sprintf('%s with parameters [%s]', $sql, implode(', ', $parameters));
        $logger->debug($message);
    }
}
