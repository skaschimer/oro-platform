<?php

namespace Oro\Bundle\ActivityListBundle\Migrations\Schema\v1_4;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\ActivityListBundle\Tools\ActivityListEntityConfigDumperExtension;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendNameGeneratorAwareTrait;
use Oro\Bundle\InstallerBundle\Migration\RenameExtendedManyToManyAssociation20;
use Oro\Bundle\InstallerBundle\Migration\UpdateTableFieldQuery;
use Oro\Bundle\MigrationBundle\Migration\ConnectionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\ConnectionAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Extension\NameGeneratorAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroActivityListBundle implements
    Migration,
    RenameExtensionAwareInterface,
    ConnectionAwareInterface,
    NameGeneratorAwareInterface
{
    use RenameExtensionAwareTrait;
    use ConnectionAwareTrait;
    use ExtendNameGeneratorAwareTrait;

    #[\Override]
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->renameActivityTables($schema, $queries);

        $queries->addQuery(new UpdateTableFieldQuery(
            'oro_activity_list',
            'related_activity_class',
            'OroCRM',
            'Oro'
        ));
    }

    private function renameActivityTables(Schema $schema, QueryBag $queries)
    {
        $helper = new RenameExtendedManyToManyAssociation20(
            $this->connection,
            $this->nameGenerator,
            $this->renameExtension
        );
        $helper->rename(
            $schema,
            $queries,
            'Oro\Bundle\ActivityListBundle\Entity\ActivityList',
            ActivityListEntityConfigDumperExtension::ASSOCIATION_KIND,
            function (array $data, $className) {
                return !empty($data['activity']['activities']);
            }
        );
    }
}
