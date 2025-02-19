<?php

namespace Oro\Bundle\UserBundle\Migrations\Schema\v1_15;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class SetOwnerForEmail implements Migration, OrderedMigrationInterface
{
    #[\Override]
    public function getOrder(): int
    {
        return 1;
    }

    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        $this->addOwnerToEmailUserTable($schema);
        $queries->addPreQuery(new UpdateEmailOriginTableQuery());
        $queries->addPostQuery(new FillEmailUserTableQuery());
    }

    /**
     * Add owner to oro_email_user table
     */
    private function addOwnerToEmailUserTable(Schema $schema): void
    {
        $table = $schema->getTable('oro_email_user');
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('user_owner_id', 'integer', ['notnull' => false]);
        $table->addIndex(['organization_id'], 'IDX_91F5CFF632C8A3DE');
        $table->addIndex(['user_owner_id'], 'IDX_91F5CFF69EB185F9');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null],
            'FK_91F5CFF632C8A3DE'
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['user_owner_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null],
            'FK_91F5CFF69EB185F9'
        );
    }
}
