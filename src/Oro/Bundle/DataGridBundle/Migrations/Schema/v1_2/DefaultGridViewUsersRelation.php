<?php

namespace Oro\Bundle\DataGridBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class DefaultGridViewUsersRelation implements Migration
{
    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        $table = $schema->createTable('oro_grid_view_user');
        $table->addColumn('grid_view_id', 'integer');
        $table->addColumn('user_id', 'integer');
        $table->setPrimaryKey(['grid_view_id', 'user_id']);
        $table->addIndex(['grid_view_id'], 'IDX_80CFBA3FBF53711B');
        $table->addIndex(['user_id'], 'IDX_80CFBA3FA76ED395');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_grid_view'),
            ['grid_view_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['user_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }
}
