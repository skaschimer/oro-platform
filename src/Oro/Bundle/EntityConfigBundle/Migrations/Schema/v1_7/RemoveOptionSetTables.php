<?php

namespace Oro\Bundle\EntityConfigBundle\Migrations\Schema\v1_7;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class RemoveOptionSetTables implements Migration, OrderedMigrationInterface
{
    #[\Override]
    public function getOrder()
    {
        return 2;
    }

    #[\Override]
    public function up(Schema $schema, QueryBag $queries)
    {
        $schema->dropTable('oro_entity_config_optset_rel');
        $schema->dropTable('oro_entity_config_optionset');
    }
}
