<?php

namespace Oro\Bundle\OrganizationBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityBundle\EntityConfig\DatagridScope;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareTrait;
use Oro\Bundle\FormBundle\Form\Type\OroResizeableRichTextType;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroOrganizationBundleInstaller implements Installation, ExtendExtensionAwareInterface
{
    use ExtendExtensionAwareTrait;

    #[\Override]
    public function getMigrationVersion(): string
    {
        return 'v1_8';
    }

    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        /** Tables generation **/
        $this->createOroOrganizationTable($schema);
        $this->createOroBusinessUnitTable($schema);

        /** Foreign keys generation **/
        $this->addOroBusinessUnitForeignKeys($schema);

        $this->addRelationsToScope($schema);
    }

    /**
     * Create oro_organization table
     */
    private function createOroOrganizationTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_organization');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->addColumn('description', 'text', ['notnull' => false]);
        $table->addColumn('created_at', 'datetime', [
            'default' => null,
            'notnull' => false,
            'comment' => '(DC2Type:datetime)'
        ]);
        $table->addColumn('updated_at', 'datetime', [
            'default' => null,
            'notnull' => false,
            'comment' => '(DC2Type:datetime)'
        ]);
        $table->addColumn('enabled', 'boolean', ['default' => '1']);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['name'], 'uniq_bb42b65d5e237e06');
    }

    /**
     * Create oro_business_unit table
     */
    private function createOroBusinessUnitTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_business_unit');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('business_unit_owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('organization_id', 'integer');
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->addColumn('phone', 'string', ['notnull' => false, 'length' => 100]);
        $table->addColumn('website', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('email', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('fax', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('created_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('updated_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn(
            'extend_description',
            'text',
            [
                'oro_options' => [
                    'extend'    => ['is_extend' => true, 'owner' => ExtendScope::OWNER_CUSTOM],
                    'datagrid'  => ['is_visible' => DatagridScope::IS_VISIBLE_FALSE],
                    'merge'     => ['display' => true],
                    'dataaudit' => ['auditable' => true],
                    'form'      => ['type' => OroResizeableRichTextType::class],
                    'view'      => ['type' => 'html'],
                ]
            ]
        );
        $table->setPrimaryKey(['id']);
        $table->addIndex(['organization_id'], 'idx_c033b2d532c8a3de');
        $table->addIndex(['business_unit_owner_id'], 'idx_c033b2d559294170');
    }

    /**
     * Add oro_business_unit foreign keys.
     */
    private function addOroBusinessUnitForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_business_unit');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_business_unit'),
            ['business_unit_owner_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    private function addRelationsToScope(Schema $schema): void
    {
        if ($schema->hasTable('oro_scope')) {
            $this->extendExtension->addManyToOneRelation(
                $schema,
                'oro_scope',
                'organization',
                'oro_organization',
                'id',
                [
                    'extend' => [
                        'owner' => ExtendScope::OWNER_CUSTOM,
                        'cascade' => ['all'],
                        'on_delete' => 'CASCADE',
                        'nullable' => true
                    ]
                ]
            );
        }
    }
}
