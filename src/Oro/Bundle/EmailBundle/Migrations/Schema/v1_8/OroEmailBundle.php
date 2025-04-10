<?php

namespace Oro\Bundle\EmailBundle\Migrations\Schema\v1_8;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroEmailBundle implements Migration
{
    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        $table = $schema->getTable('oro_email_attachment');
        $table->addColumn('file_id', 'integer', ['notnull' => false]);
        $table->addUniqueIndex(['file_id'], 'UNIQ_F4427F2393CB796C');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_attachment_file'),
            ['file_id'],
            ['id'],
            ['onDelete' => 'SET NULL'],
            'FK_F4427F2393CB796C'
        );
    }
}
