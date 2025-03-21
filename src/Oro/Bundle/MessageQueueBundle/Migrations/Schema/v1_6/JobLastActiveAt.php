<?php

namespace Oro\Bundle\MessageQueueBundle\Migrations\Schema\v1_6;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class JobLastActiveAt implements Migration
{
    #[\Override]
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_message_queue_job');
        if (!$table->hasColumn('last_active_at')) {
            $table->addColumn('last_active_at', 'datetime', ['notnull' => false]);
        }
    }
}
