<?php

namespace Oro\Bundle\SegmentBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class OroSegmentBundleInstaller implements Installation
{
    #[\Override]
    public function getMigrationVersion(): string
    {
        return 'v1_11';
    }

    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        /** Tables generation **/
        $this->createOroSegmentTypeTable($schema);
        $this->createOroSegmentSnapshotTable($schema);
        $this->createOroSegmentTable($schema);

        /** Foreign keys generation **/
        $this->addOroSegmentSnapshotForeignKeys($schema);
        $this->addOroSegmentForeignKeys($schema);
    }

    /**
     * Create oro_segment_type table
     */
    private function createOroSegmentTypeTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_segment_type');
        $table->addColumn('name', 'string', ['length' => 32]);
        $table->addColumn('label', 'string', ['length' => 255]);
        $table->setPrimaryKey(['name']);
        $table->addUniqueIndex(['label'], 'uniq_29d10555ea750e8');
    }

    /**
     * Create oro_segment_snapshot table
     */
    private function createOroSegmentSnapshotTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_segment_snapshot');
        $table->addColumn('id', 'bigint', ['autoincrement' => true]);
        $table->addColumn('segment_id', 'integer');
        $table->addColumn('integer_entity_id', 'integer', ['notnull' => false]);
        $table->addColumn('entity_id', 'string', ['length' => 255, 'notnull' => false]);
        $table->addColumn('createdat', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['segment_id', 'entity_id'], 'uniq_43b8bb67db296aad81257d5d');
        $table->addUniqueIndex(
            ['segment_id', 'integer_entity_id'],
            'oro_segment_snapshot_segment_id_integer_entity_id_idx'
        );
        $table->addIndex(['segment_id'], 'idx_43b8bb67db296aad');
        $table->addIndex(['integer_entity_id'], 'sgmnt_snpsht_int_entity_idx');
        $table->addIndex(['entity_id'], 'sgmnt_snpsht_str_entity_idx');
    }

    /**
     * Create oro_segment table
     */
    private function createOroSegmentTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_segment');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('type', 'string', ['length' => 32]);
        $table->addColumn('business_unit_owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->addColumn('name_lowercase', 'string', ['length' => 255]);
        $table->addColumn('description', 'text', ['notnull' => false]);
        $table->addColumn('entity', 'string', ['length' => 255]);
        $table->addColumn('definition', 'text');
        $table->addColumn('createdat', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('updatedat', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('last_run', 'datetime', ['notnull' => false, 'comment' => '(DC2Type:datetime)']);
        $table->addColumn('records_limit', 'integer', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['organization_id'], 'idx_d02603b332c8a3de');
        $table->addUniqueIndex(['name_lowercase'], 'uniq_d02603b37edd63ff');
        $table->addIndex(['business_unit_owner_id'], 'idx_d02603b359294170');
        $table->addIndex(['type'], 'idx_d02603b38cde5729');
    }

    /**
     * Add oro_segment_snapshot foreign keys.
     */
    private function addOroSegmentSnapshotForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_segment_snapshot');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_segment'),
            ['segment_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * Add oro_segment foreign keys.
     */
    private function addOroSegmentForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_segment');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_segment_type'),
            ['type'],
            ['name'],
            ['onUpdate' => null, 'onDelete' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_business_unit'),
            ['business_unit_owner_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
    }
}
