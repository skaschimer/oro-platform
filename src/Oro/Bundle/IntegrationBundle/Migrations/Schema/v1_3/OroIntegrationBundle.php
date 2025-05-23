<?php

namespace Oro\Bundle\IntegrationBundle\Migrations\Schema\v1_3;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroIntegrationBundle implements Migration, OrderedMigrationInterface
{
    #[\Override]
    public function getOrder(): int
    {
        return 1;
    }

    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        $table = $schema->getTable('oro_integration_channel');
        $table->addColumn('enabled', 'boolean', ['notnull' => false]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('synchronization_settings', 'text', ['notnull' => true, 'comment' => '(DC2Type:object)']);
        $table->addColumn('mapping_settings', 'text', ['notnull' => true, 'comment' => '(DC2Type:object)']);
        $table->addIndex(['organization_id'], 'IDX_55B9B9C532C8A3DE');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null],
            'FK_55B9B9C532C8A3DE'
        );

        $queries->addPostQuery(new MigrateValuesQuery());
    }
}
