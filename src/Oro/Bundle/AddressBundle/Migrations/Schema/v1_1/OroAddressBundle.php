<?php

namespace Oro\Bundle\AddressBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroAddressBundle implements Migration, RenameExtensionAwareInterface
{
    use RenameExtensionAwareTrait;

    #[\Override]
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->renameExtension->renameTable(
            $schema,
            $queries,
            'oro_dictionary_country_translation',
            'oro_dictionary_country_trans'
        );
        $this->renameExtension->renameTable(
            $schema,
            $queries,
            'oro_dictionary_region_translation',
            'oro_dictionary_region_trans'
        );
    }
}
