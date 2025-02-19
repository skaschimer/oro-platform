<?php

namespace Oro\Bundle\MigrationBundle\Migration\Schema;

use Doctrine\DBAL\Schema\Schema as BaseSchema;
use Doctrine\DBAL\Schema\Table as BaseTable;

/**
 * The aim of this class is to provide a way extend doctrine Table class
 * To do this just define your table class name in TABLE_CLASS constant in an extended class
 * and override createTableObject if your table class constructor need an additional arguments
 */
class Schema extends BaseSchema
{
    /**
     * Used table class, define TABLE_CLASS constant in an extended class to extend the table class
     * Important: your class must extend Oro\Bundle\MigrationBundle\Migration\Schema\Table class
     *            or extend Doctrine\DBAL\Schema\Table class and must have __construct(array $args) method
     */
    const TABLE_CLASS = 'Doctrine\DBAL\Schema\Table';

    /**
     * Creates an instance of TABLE_CLASS class
     *
     * @param array $args An arguments for TABLE_CLASS class constructor
     *                    An instance of a base table is in 'table' element
     * @return BaseTable
     */
    protected function createTableObject(array $args)
    {
        $tableClass = static::TABLE_CLASS;

        return new $tableClass($args);
    }

    #[\Override]
    public function createTable($tableName)
    {
        parent::createTable($tableName);

        /**
         * Add options of the created table,
         * although the same logic exists in Doctrine\DBAL\Schema\Schema::createTable.
         * It is required to do here because _addTable() method creates a new instance of Table object
         * and as result the options are added to wrong Table object.
         */
        $table = $this->getTable($tableName);
        foreach ($this->_schemaConfig->getDefaultTableOptions() as $name => $value) {
            $table->addOption($name, $value);
        }

        return $table;
    }

    // @codingStandardsIgnoreStart
    #[\Override]
    protected function _addTable(BaseTable $table)
    {
        if (get_class($table) !== static::TABLE_CLASS && static::TABLE_CLASS !== 'Doctrine\DBAL\Schema\Table') {
            $table = $this->createTableObject(['table' => $table]);
        }
        parent::_addTable($table);
    }
    // @codingStandardsIgnoreEnd
}
