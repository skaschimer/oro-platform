<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Metadata;

use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\ExternalLinkMetadata;
use Oro\Bundle\ApiBundle\Metadata\MetaAttributeMetadata;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class AssociationMetadataTest extends TestCase
{
    private EntityMetadata $entityMetadata;

    #[\Override]
    protected function setUp(): void
    {
        $this->entityMetadata = new EntityMetadata('entityClassName');
        $this->entityMetadata->setInheritedType(true);
    }

    public function testClone(): void
    {
        $associationMetadata = new AssociationMetadata();
        $associationMetadata->setName('testName');
        $associationMetadata->setPropertyPath('testPropertyPath');
        $associationMetadata->setDataType('testDataType');
        $associationMetadata->setTargetClassName('targetClassName');
        $associationMetadata->setBaseTargetClassName('baseTargetClassName');
        $associationMetadata->setAcceptableTargetClassNames(['targetClassName1']);
        $associationMetadata->setIsCollection(true);
        $associationMetadata->setIsNullable(true);
        $associationMetadata->setCollapsed(true);
        $targetEntityMetadata = new EntityMetadata('TargetEntityClassName');
        $associationMetadata->setTargetMetadata($targetEntityMetadata);
        $associationMetadata->addMetaProperty(new MetaAttributeMetadata('metaProperty1', 'string'));
        $associationMetadata->addRelationshipMetaProperty(new MetaAttributeMetadata('metaProperty2', 'string'));
        $associationMetadata->addLink('link1', new ExternalLinkMetadata('url1'));
        $associationMetadata->addRelationshipLink('link2', new ExternalLinkMetadata('url2'));

        $associationMetadataClone = clone $associationMetadata;

        self::assertEquals($associationMetadata, $associationMetadataClone);
        self::assertNotSame($targetEntityMetadata, $associationMetadataClone->getTargetMetadata());
    }

    public function testCloneWithoutTargetMetadata(): void
    {
        $associationMetadata = new AssociationMetadata();
        $associationMetadata->setName('testName');

        $associationMetadataClone = clone $associationMetadata;

        self::assertEquals($associationMetadata, $associationMetadataClone);
        self::assertNull($associationMetadataClone->getTargetMetadata());
    }

    public function testToArray(): void
    {
        $associationMetadata = new AssociationMetadata();
        $associationMetadata->setName('testName');
        $associationMetadata->setPropertyPath('testPropertyPath');
        $associationMetadata->setDataType('testDataType');
        $associationMetadata->setTargetClassName('targetClassName');
        $associationMetadata->setBaseTargetClassName('baseTargetClassName');
        $associationMetadata->setAcceptableTargetClassNames(['targetClassName1', 'targetClassName2']);
        $associationMetadata->setAssociationType('manyToMany');
        $associationMetadata->setIsCollection(true);
        $associationMetadata->setIsNullable(true);
        $associationMetadata->setCollapsed(true);
        $associationMetadata->setTargetMetadata($this->entityMetadata);
        $associationMetadata->addMetaProperty(new MetaAttributeMetadata('metaProperty1', 'string'));
        $associationMetadata->addRelationshipMetaProperty(new MetaAttributeMetadata('metaProperty2', 'string'));
        $associationMetadata->addLink('link1', new ExternalLinkMetadata('url1'));
        $associationMetadata->addRelationshipLink('link2', new ExternalLinkMetadata('url2'));

        self::assertEquals(
            [
                'name'                         => 'testName',
                'property_path'                => 'testPropertyPath',
                'data_type'                    => 'testDataType',
                'nullable'                     => true,
                'collapsed'                    => true,
                'association_type'             => 'manyToMany',
                'collection'                   => true,
                'target_class'                 => 'targetClassName',
                'base_target_class'            => 'baseTargetClassName',
                'acceptable_target_classes'    => ['targetClassName1', 'targetClassName2'],
                'target_metadata'              => [
                    'class'     => 'entityClassName',
                    'inherited' => true
                ],
                'meta_properties'              => [
                    'metaProperty1' => [
                        'data_type' => 'string'
                    ]
                ],
                'relationship_meta_properties' => [
                    'metaProperty2' => [
                        'data_type' => 'string'
                    ]
                ],
                'links'                        => [
                    'link1' => [
                        'url_template' => 'url1'
                    ]
                ],
                'relationship_links'           => [
                    'link2' => [
                        'url_template' => 'url2'
                    ]
                ]
            ],
            $associationMetadata->toArray()
        );
    }

    public function testToArrayWithRequiredPropertiesOnly(): void
    {
        $associationMetadata = new AssociationMetadata();
        $associationMetadata->setName('testName');

        self::assertEquals(
            [
                'name'             => 'testName',
                'nullable'         => false,
                'collapsed'        => false,
                'association_type' => null,
                'collection'       => false
            ],
            $associationMetadata->toArray()
        );
    }

    public function testToArrayInputOnlyAssociation(): void
    {
        $associationMetadata = new AssociationMetadata();
        $associationMetadata->setName('testName');
        $associationMetadata->setDirection(true, false);

        self::assertEquals(
            [
                'name'             => 'testName',
                'direction'        => 'input-only',
                'nullable'         => false,
                'collapsed'        => false,
                'association_type' => null,
                'collection'       => false
            ],
            $associationMetadata->toArray()
        );
    }

    public function testToArrayOutputOnlyAssociation(): void
    {
        $associationMetadata = new AssociationMetadata();
        $associationMetadata->setName('testName');
        $associationMetadata->setDirection(false, true);

        self::assertEquals(
            [
                'name'             => 'testName',
                'direction'        => 'output-only',
                'nullable'         => false,
                'collapsed'        => false,
                'association_type' => null,
                'collection'       => false
            ],
            $associationMetadata->toArray()
        );
    }

    public function testToArrayHiddenField(): void
    {
        $associationMetadata = new AssociationMetadata();
        $associationMetadata->setName('testName');
        $associationMetadata->setHidden();

        self::assertEquals(
            [
                'name'             => 'testName',
                'hidden'           => true,
                'nullable'         => false,
                'collapsed'        => false,
                'association_type' => null,
                'collection'       => false
            ],
            $associationMetadata->toArray()
        );
    }

    public function testToArrayWhenEmptyAcceptableTargetsAllowedAndAcceptableTargetClassNamesAreNotEmpty(): void
    {
        $associationMetadata = new AssociationMetadata();
        $associationMetadata->setName('testName');
        $associationMetadata->setAcceptableTargetClassNames(['Test\Target1']);

        self::assertEquals(
            [
                'name'                      => 'testName',
                'nullable'                  => false,
                'collapsed'                 => false,
                'association_type'          => null,
                'collection'                => false,
                'acceptable_target_classes' => ['Test\Target1']
            ],
            $associationMetadata->toArray()
        );
    }

    public function testToArrayWhenEmptyAcceptableTargetsNotAllowedAndAcceptableTargetClassNamesAreNotEmpty(): void
    {
        $associationMetadata = new AssociationMetadata();
        $associationMetadata->setName('testName');
        $associationMetadata->setEmptyAcceptableTargetsAllowed(false);
        $associationMetadata->setAcceptableTargetClassNames(['Test\Target1']);

        self::assertEquals(
            [
                'name'                      => 'testName',
                'nullable'                  => false,
                'collapsed'                 => false,
                'association_type'          => null,
                'collection'                => false,
                'acceptable_target_classes' => ['Test\Target1']
            ],
            $associationMetadata->toArray()
        );
    }

    public function testToArrayWhenEmptyAcceptableTargetsNotAllowedAndAcceptableTargetClassNamesAreEmpty(): void
    {
        $associationMetadata = new AssociationMetadata();
        $associationMetadata->setName('testName');
        $associationMetadata->setEmptyAcceptableTargetsAllowed(false);

        self::assertEquals(
            [
                'name'                            => 'testName',
                'nullable'                        => false,
                'collapsed'                       => false,
                'association_type'                => null,
                'collection'                      => false,
                'reject_empty_acceptable_targets' => true
            ],
            $associationMetadata->toArray()
        );
    }

    public function testNameInConstructor(): void
    {
        $associationMetadata = new AssociationMetadata('associationName');
        self::assertEquals('associationName', $associationMetadata->getName());
    }

    public function testName(): void
    {
        $associationMetadata = new AssociationMetadata();

        self::assertNull($associationMetadata->getName());
        $associationMetadata->setName('associationName');
        self::assertEquals('associationName', $associationMetadata->getName());
    }

    public function testPropertyPath(): void
    {
        $associationMetadata = new AssociationMetadata();

        self::assertNull($associationMetadata->getPropertyPath());
        $associationMetadata->setName('name');
        self::assertEquals('name', $associationMetadata->getPropertyPath());
        $associationMetadata->setPropertyPath('propertyPath');
        self::assertEquals('propertyPath', $associationMetadata->getPropertyPath());
        $associationMetadata->setPropertyPath(ConfigUtil::IGNORE_PROPERTY_PATH);
        self::assertNull($associationMetadata->getPropertyPath());
        $associationMetadata->setPropertyPath(null);
        self::assertEquals('name', $associationMetadata->getPropertyPath());
    }

    public function testDataType(): void
    {
        $associationMetadata = new AssociationMetadata();

        self::assertNull($associationMetadata->getDataType());
        $associationMetadata->setDataType('associationType');
        self::assertEquals('associationType', $associationMetadata->getDataType());
    }

    public function testDirection(): void
    {
        $associationMetadata = new AssociationMetadata();

        self::assertTrue($associationMetadata->isInput());
        self::assertTrue($associationMetadata->isOutput());
        $associationMetadata->setDirection(true, false);
        self::assertTrue($associationMetadata->isInput());
        self::assertFalse($associationMetadata->isOutput());
        $associationMetadata->setDirection(false, true);
        self::assertFalse($associationMetadata->isInput());
        self::assertTrue($associationMetadata->isOutput());
        $associationMetadata->setDirection(true, false);
        self::assertTrue($associationMetadata->isInput());
        self::assertFalse($associationMetadata->isOutput());
        $associationMetadata->setDirection(false, false);
        self::assertFalse($associationMetadata->isInput());
        self::assertFalse($associationMetadata->isOutput());
        $associationMetadata->setDirection(true, true);
        self::assertTrue($associationMetadata->isInput());
        self::assertTrue($associationMetadata->isOutput());
    }

    public function testHidden(): void
    {
        $associationMetadata = new AssociationMetadata();

        self::assertFalse($associationMetadata->isHidden());
        self::assertTrue($associationMetadata->isInput());
        self::assertTrue($associationMetadata->isOutput());
        $associationMetadata->setHidden();
        self::assertTrue($associationMetadata->isHidden());
        self::assertFalse($associationMetadata->isInput());
        self::assertFalse($associationMetadata->isOutput());
    }

    public function testTargetClassName(): void
    {
        $associationMetadata = new AssociationMetadata();

        self::assertNull($associationMetadata->getTargetClassName());
        $associationMetadata->setTargetClassName('targetClassName');
        self::assertEquals('targetClassName', $associationMetadata->getTargetClassName());
    }

    public function testBaseTargetClassName(): void
    {
        $associationMetadata = new AssociationMetadata();

        self::assertNull($associationMetadata->getBaseTargetClassName());
        $associationMetadata->setBaseTargetClassName('targetClassName');
        self::assertEquals('targetClassName', $associationMetadata->getBaseTargetClassName());
    }

    public function testAcceptableTargetClassName(): void
    {
        $associationMetadata = new AssociationMetadata();

        self::assertEquals([], $associationMetadata->getAcceptableTargetClassNames());
        $associationMetadata->setAcceptableTargetClassNames(['targetClassName1', 'targetClassName2']);
        self::assertEquals(
            ['targetClassName1', 'targetClassName2'],
            $associationMetadata->getAcceptableTargetClassNames()
        );
        $associationMetadata->removeAcceptableTargetClassName('targetClassName1');
        $associationMetadata->addAcceptableTargetClassName('targetClassName3');
        self::assertEquals(
            ['targetClassName2', 'targetClassName3'],
            $associationMetadata->getAcceptableTargetClassNames()
        );
    }

    public function testAcceptableTargetClassNames(): void
    {
        $associationMetadata = new AssociationMetadata();

        self::assertTrue($associationMetadata->isEmptyAcceptableTargetsAllowed());
        $associationMetadata->setEmptyAcceptableTargetsAllowed(false);
        self::assertFalse($associationMetadata->isEmptyAcceptableTargetsAllowed());
    }

    public function testAssociationType(): void
    {
        $associationMetadata = new AssociationMetadata();

        self::assertNull($associationMetadata->getAssociationType());
        $associationMetadata->setAssociationType('manyToOne');
        self::assertEquals('manyToOne', $associationMetadata->getAssociationType());
    }

    public function testCollection(): void
    {
        $associationMetadata = new AssociationMetadata();

        self::assertFalse($associationMetadata->isCollection());
        $associationMetadata->setIsCollection(true);
        self::assertTrue($associationMetadata->isCollection());
    }

    public function testNullable(): void
    {
        $associationMetadata = new AssociationMetadata();

        self::assertFalse($associationMetadata->isNullable());
        $associationMetadata->setIsNullable(true);
        self::assertTrue($associationMetadata->isNullable());
    }

    public function testCollapsed(): void
    {
        $associationMetadata = new AssociationMetadata();

        self::assertFalse($associationMetadata->isCollapsed());
        $associationMetadata->setCollapsed(true);
        self::assertTrue($associationMetadata->isCollapsed());
    }

    public function testTargetMetadata(): void
    {
        $associationMetadata = new AssociationMetadata();

        self::assertNull($associationMetadata->getTargetMetadata());
        $associationMetadata->setTargetMetadata($this->entityMetadata);
        self::assertEquals($this->entityMetadata, $associationMetadata->getTargetMetadata());
    }

    public function testLinks(): void
    {
        $associationMetadata = new AssociationMetadata();
        self::assertCount(0, $associationMetadata->getLinks());
        self::assertFalse($associationMetadata->hasLink('unknown'));
        self::assertNull($associationMetadata->getLink('unknown'));

        $link1 = new ExternalLinkMetadata('url1');
        self::assertSame($link1, $associationMetadata->addLink('link1', $link1));
        $link2 = new ExternalLinkMetadata('url2');
        self::assertSame($link2, $associationMetadata->addLink('link2', $link2));
        self::assertCount(2, $associationMetadata->getLinks());

        self::assertTrue($associationMetadata->hasLink('link1'));
        self::assertSame($link1, $associationMetadata->getLink('link1'));

        $associationMetadata->removeLink('link1');
        self::assertCount(1, $associationMetadata->getLinks());
        self::assertFalse($associationMetadata->hasLink('link1'));
        self::assertTrue($associationMetadata->hasLink('link2'));

        $associationMetadata->removeLink('link2');
        self::assertCount(0, $associationMetadata->getLinks());
        self::assertFalse($associationMetadata->hasLink('link2'));
    }

    public function testRelationshipLinks(): void
    {
        $associationMetadata = new AssociationMetadata();
        self::assertCount(0, $associationMetadata->getRelationshipLinks());
        self::assertFalse($associationMetadata->hasRelationshipLink('unknown'));
        self::assertNull($associationMetadata->getRelationshipLink('unknown'));

        $link1 = new ExternalLinkMetadata('url1');
        self::assertSame($link1, $associationMetadata->addRelationshipLink('link1', $link1));
        $link2 = new ExternalLinkMetadata('url2');
        self::assertSame($link2, $associationMetadata->addRelationshipLink('link2', $link2));
        self::assertCount(2, $associationMetadata->getRelationshipLinks());

        self::assertTrue($associationMetadata->hasRelationshipLink('link1'));
        self::assertSame($link1, $associationMetadata->getRelationshipLink('link1'));

        $associationMetadata->removeRelationshipLink('link1');
        self::assertCount(1, $associationMetadata->getRelationshipLinks());
        self::assertFalse($associationMetadata->hasRelationshipLink('link1'));
        self::assertTrue($associationMetadata->hasRelationshipLink('link2'));

        $associationMetadata->removeRelationshipLink('link2');
        self::assertCount(0, $associationMetadata->getRelationshipLinks());
        self::assertFalse($associationMetadata->hasRelationshipLink('link2'));
    }

    public function testMetaProperties(): void
    {
        $associationMetadata = new AssociationMetadata();
        self::assertCount(0, $associationMetadata->getMetaProperties());
        self::assertFalse($associationMetadata->hasMetaProperty('unknown'));
        self::assertNull($associationMetadata->getMetaProperty('unknown'));

        $metaProperty1 = new MetaAttributeMetadata('metaProperty1', 'string');
        self::assertSame($metaProperty1, $associationMetadata->addMetaProperty($metaProperty1));
        $metaProperty2 = new MetaAttributeMetadata('metaProperty2', 'string');
        self::assertSame($metaProperty2, $associationMetadata->addMetaProperty($metaProperty2));
        self::assertCount(2, $associationMetadata->getMetaProperties());

        self::assertTrue($associationMetadata->hasMetaProperty('metaProperty1'));
        self::assertSame($metaProperty1, $associationMetadata->getMetaProperty('metaProperty1'));

        $associationMetadata->removeMetaProperty('metaProperty1');
        self::assertCount(1, $associationMetadata->getMetaProperties());
        self::assertFalse($associationMetadata->hasMetaProperty('metaProperty1'));
        self::assertTrue($associationMetadata->hasMetaProperty('metaProperty2'));

        $associationMetadata->removeMetaProperty('metaProperty2');
        self::assertCount(0, $associationMetadata->getMetaProperties());
        self::assertFalse($associationMetadata->hasMetaProperty('metaProperty2'));
    }

    public function testRelationshipMetaProperties(): void
    {
        $associationMetadata = new AssociationMetadata();
        self::assertCount(0, $associationMetadata->getRelationshipMetaProperties());
        self::assertFalse($associationMetadata->hasRelationshipMetaProperty('unknown'));
        self::assertNull($associationMetadata->getRelationshipMetaProperty('unknown'));

        $metaProperty1 = new MetaAttributeMetadata('metaProperty1', 'string');
        self::assertSame($metaProperty1, $associationMetadata->addRelationshipMetaProperty($metaProperty1));
        $metaProperty2 = new MetaAttributeMetadata('metaProperty2', 'string');
        self::assertSame($metaProperty2, $associationMetadata->addRelationshipMetaProperty($metaProperty2));
        self::assertCount(2, $associationMetadata->getRelationshipMetaProperties());

        self::assertTrue($associationMetadata->hasRelationshipMetaProperty('metaProperty1'));
        self::assertSame($metaProperty1, $associationMetadata->getRelationshipMetaProperty('metaProperty1'));

        $associationMetadata->removeRelationshipMetaProperty('metaProperty1');
        self::assertCount(1, $associationMetadata->getRelationshipMetaProperties());
        self::assertFalse($associationMetadata->hasRelationshipMetaProperty('metaProperty1'));
        self::assertTrue($associationMetadata->hasRelationshipMetaProperty('metaProperty2'));

        $associationMetadata->removeRelationshipMetaProperty('metaProperty2');
        self::assertCount(0, $associationMetadata->getRelationshipMetaProperties());
        self::assertFalse($associationMetadata->hasRelationshipMetaProperty('metaProperty2'));
    }
}
