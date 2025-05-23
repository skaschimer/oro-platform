<?php

namespace Oro\Bundle\NotificationBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddMassNotificationTable implements Migration
{
    #[\Override]
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->createOroNotificationMassNotifTable($schema);
    }

    /**
     * Create oro_notification_mass_notif table
     */
    protected function createOroNotificationMassNotifTable(Schema $schema)
    {
        $table = $schema->createTable('oro_notification_mass_notif');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('email', 'string', ['length' => 255]);
        $table->addColumn('sender', 'string', ['length' => 255]);
        $table->addColumn('subject', 'string', ['length' => 255]);
        $table->addColumn('body', 'text', ['notnull' => false]);
        $table->addColumn('scheduledAt', 'datetime', []);
        $table->addColumn('processedAt', 'datetime', []);
        $table->addColumn('status', 'integer', []);
        $table->setPrimaryKey(['id']);
    }
}
