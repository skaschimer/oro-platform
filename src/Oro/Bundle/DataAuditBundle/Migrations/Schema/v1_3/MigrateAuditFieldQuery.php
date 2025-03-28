<?php

namespace Oro\Bundle\DataAuditBundle\Migrations\Schema\v1_3;

use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Oro\Bundle\DataAuditBundle\Model\AuditFieldTypeRegistry;
use Oro\Bundle\MigrationBundle\Migration\ConnectionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\ConnectionAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\MigrationQuery;
use Psr\Log\LoggerInterface;

class MigrateAuditFieldQuery implements MigrationQuery, ConnectionAwareInterface
{
    use ConnectionAwareTrait;

    const LIMIT = 100;

    #[\Override]
    public function getDescription()
    {
        return 'Copy audit data into oro_audit_field table.';
    }

    #[\Override]
    public function execute(LoggerInterface $logger)
    {
        $steps = ceil($this->getAuditCount() / static::LIMIT);

        $auditQb = $this->createAuditQb()
            ->setMaxResults(static::LIMIT);

        for ($i = 0; $i < $steps; $i++) {
            $rows = $auditQb
                ->setFirstResult($i * static::LIMIT)
                ->execute()
                ->fetchAllAssociative();

            foreach ($rows as $row) {
                $this->processRow($row);
            }
        }
    }

    private function processRow(array $row)
    {
        $data = $row['data'];

        try {
            $data = Type::getType(Types::ARRAY)
                ->convertToPHPValue($row['data'], $this->connection->getDatabasePlatform());
        } catch (ConversionException $ex) {
        }

        if (is_array($data)) {
            $this->processArrayData($row, $data);
        } else {
            if (!is_scalar($data)) {
                $data = serialize($data);
            }
            $this->processTextData($row, $data);
        }
    }

    /**
     * @param array $row
     * @param string $data
     */
    private function processTextData(array $row, $data)
    {
        $dataType = AuditFieldTypeRegistry::getAuditType('text');

        $dbData = [
            'audit_id' => $row['id'],
            'data_type' => $dataType,
            'field' => '__unknown__',
            sprintf('old_%s', $dataType) => $data,
            sprintf('new_%s', $dataType) => $data,
            'visible' => false,
        ];

        $types = [
            'integer',
            'string',
            'string',
            $dataType,
            $dataType,
            'boolean',
        ];

        $this->connection->insert('oro_audit_field', $dbData, $types);
    }

    private function processArrayData(array $row, array $data)
    {
        foreach ($data as $field => $values) {
            $visible = true;

            $fieldType = $this->getFieldType($row['entity_id'], $field);
            $dataType = null;
            if (!AuditFieldTypeRegistry::hasType($fieldType)
                || !array_key_exists('old', $values)
                || !array_key_exists('new', $values)
            ) {
                $dataType = 'array';
                $visible  = false;
            } else {
                $dataType = AuditFieldTypeRegistry::getAuditType($fieldType);
            }

            $dbData = [
                'audit_id' => $row['id'],
                'data_type' => $dataType,
                'field' => $field,
                sprintf('old_%s', $dataType) => $this->parseValue($values, 'old'),
                sprintf('new_%s', $dataType) => $this->parseValue($values, 'new'),
                'visible' => $visible,
            ];

            $types = [
                'integer',
                'string',
                'string',
                $dataType,
                $dataType,
                'boolean',
            ];

            $this->connection->insert('oro_audit_field', $dbData, $types);
        }
    }

    /**
     * @param mixed $values
     * @param string $key
     *
     * @return mixed
     */
    private function parseValue($values, $key)
    {
        if (!array_key_exists($key, $values)) {
            return $values;
        }

        $value = $values[$key];
        if (is_array($value) && array_key_exists('value', $value)) {
            return $value['value'];
        }

        return $value;
    }

    /**
     * @param int $entityId
     * @param string $field
     *
     * @return string|false
     */
    private function getFieldType($entityId, $field)
    {
        return $this->connection->createQueryBuilder()
            ->select('ecf.type')
            ->from('oro_entity_config_field', 'ecf')
            ->where('ecf.entity_id = :entity_id')
            ->andWhere('ecf.field_name = :field_name')
            ->setParameters([
                'entity_id' => $entityId,
                'field_name' => $field,
            ])
            ->execute()
            ->fetchOne();
    }

    /**
     * @return int
     */
    private function getAuditCount()
    {
        return $this->createAuditQb()
            ->select('COUNT(1)')
            ->execute()
            ->fetchOne();
    }

    /**
     * @return QueryBuilder
     */
    private function createAuditQb()
    {
        return $this->connection->createQueryBuilder()
            ->select('a.id AS id, a.data AS data, ec.id AS entity_id')
            ->from('oro_audit', 'a')
            ->join('a', 'oro_entity_config', 'ec', 'a.object_class = ec.class_name');
    }
}
