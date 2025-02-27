<?php

namespace Oro\Bundle\DataAuditBundle\Migrations\Schema\v1_6;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class SetNotNullable implements Migration, OrderedMigrationInterface
{
    #[\Override]
    public function getOrder()
    {
        return 30;
    }

    #[\Override]
    public function up(Schema $schema, QueryBag $queries)
    {
        $auditTable = $schema->getTable('oro_audit');
        $auditTable->getColumn('type')
            ->setType(Type::getType(Types::STRING))
            ->setOptions(['length' => 255, 'notnull' => true]);
        $auditTable->addIndex(['type'], 'idx_oro_audit_type');
    }
}
