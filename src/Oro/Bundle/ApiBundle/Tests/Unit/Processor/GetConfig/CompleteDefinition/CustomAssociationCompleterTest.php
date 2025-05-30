<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetConfig\CompleteDefinition;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Model\EntityIdentifier;
use Oro\Bundle\ApiBundle\Processor\GetConfig\CompleteDefinition\CompleteAssociationHelper;
use Oro\Bundle\ApiBundle\Processor\GetConfig\CompleteDefinition\CustomAssociationCompleter;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Provider\ExtendedAssociationProvider;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class CustomAssociationCompleterTest extends CompleteDefinitionHelperTestCase
{
    private DoctrineHelper&MockObject $doctrineHelper;
    private ConfigProvider&MockObject $configProvider;
    private ExtendedAssociationProvider&MockObject $extendedAssociationProvider;
    private CustomAssociationCompleter $customAssociationCompleter;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->configProvider = $this->createMock(ConfigProvider::class);
        $this->extendedAssociationProvider = $this->createMock(ExtendedAssociationProvider::class);

        $this->customAssociationCompleter = new CustomAssociationCompleter(
            $this->doctrineHelper,
            new CompleteAssociationHelper($this->configProvider),
            $this->extendedAssociationProvider
        );
    }

    private function getClassMetadataWithIdField(string $className, string $idFieldDataType): ClassMetadata
    {
        $target1EntityMetadata = $this->getClassMetadataMock($className);
        $target1EntityMetadata->expects(self::once())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $target1EntityMetadata->expects(self::once())
            ->method('getTypeOfField')
            ->with('id')
            ->willReturn($idFieldDataType);

        return $target1EntityMetadata;
    }

    public function testCompleteToOneExtendedAssociationWithoutAssociationKind(): void
    {
        $dataType = 'association:manyToOne';
        $associationName = 'association1';
        $config = $this->createConfigObject([
            'fields' => [
                $associationName => ['data_type' => $dataType]
            ]
        ]);
        $version = self::TEST_VERSION;
        $requestType = new RequestType([self::TEST_REQUEST_TYPE]);

        $this->extendedAssociationProvider->expects(self::once())
            ->method('getExtendedAssociationTargets')
            ->with(self::TEST_CLASS_NAME, 'manyToOne', null, $version, $requestType)
            ->willReturn(['Test\TargetClass1' => 'field1']);

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->with(EntityIdentifier::class, $version, $requestType)
            ->willReturn($this->createRelationConfigObject([
                'identifier_field_names' => ['id'],
                'fields'                 => [
                    'id' => ['data_type' => 'integer']
                ]
            ]));

        $result = $this->customAssociationCompleter->completeCustomDataType(
            $this->getClassMetadataMock(self::TEST_CLASS_NAME),
            $config,
            $associationName,
            $config->getField($associationName),
            $dataType,
            $version,
            $requestType
        );
        self::assertTrue($result);

        $this->assertConfig(
            [
                'fields' => [
                    $associationName => [
                        'data_type'              => $dataType,
                        'target_class'           => EntityIdentifier::class,
                        'target_type'            => 'to-one',
                        'depends_on'             => ['field1'],
                        'exclusion_policy'       => 'all',
                        'identifier_field_names' => ['id'],
                        'collapse'               => true,
                        'fields'                 => [
                            'id' => ['data_type' => 'integer']
                        ]
                    ]
                ]
            ],
            $config
        );
    }

    public function testCompleteToManyExtendedAssociationWithAssociationKind(): void
    {
        $dataType = 'association:manyToMany:kind';
        $associationName = 'association1';
        $config = $this->createConfigObject([
            'fields' => [
                $associationName => ['data_type' => $dataType]
            ]
        ]);
        $version = self::TEST_VERSION;
        $requestType = new RequestType([self::TEST_REQUEST_TYPE]);

        $this->extendedAssociationProvider->expects(self::once())
            ->method('getExtendedAssociationTargets')
            ->with(self::TEST_CLASS_NAME, 'manyToMany', 'kind', $version, $requestType)
            ->willReturn(['Test\TargetClass1' => 'field1']);

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->with(EntityIdentifier::class, $version, $requestType)
            ->willReturn($this->createRelationConfigObject([
                'identifier_field_names' => ['id'],
                'fields'                 => [
                    'id' => ['data_type' => 'integer']
                ]
            ]));

        $result = $this->customAssociationCompleter->completeCustomDataType(
            $this->getClassMetadataMock(self::TEST_CLASS_NAME),
            $config,
            $associationName,
            $config->getField($associationName),
            $dataType,
            $version,
            $requestType
        );
        self::assertTrue($result);

        $this->assertConfig(
            [
                'fields' => [
                    $associationName => [
                        'data_type'              => $dataType,
                        'target_class'           => EntityIdentifier::class,
                        'target_type'            => 'to-many',
                        'depends_on'             => ['field1'],
                        'exclusion_policy'       => 'all',
                        'identifier_field_names' => ['id'],
                        'collapse'               => true,
                        'fields'                 => [
                            'id' => ['data_type' => 'integer']
                        ]
                    ]
                ]
            ],
            $config
        );
    }

    public function testCompleteMultipleManyToOneExtendedAssociation(): void
    {
        $dataType = 'association:multipleManyToOne';
        $associationName = 'association1';
        $config = $this->createConfigObject([
            'fields' => [
                $associationName => ['data_type' => $dataType]
            ]
        ]);
        $version = self::TEST_VERSION;
        $requestType = new RequestType([self::TEST_REQUEST_TYPE]);

        $this->extendedAssociationProvider->expects(self::once())
            ->method('getExtendedAssociationTargets')
            ->with(self::TEST_CLASS_NAME, 'multipleManyToOne', null, $version, $requestType)
            ->willReturn(['Test\TargetClass1' => 'field1']);

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->with(EntityIdentifier::class, $version, $requestType)
            ->willReturn($this->createRelationConfigObject([
                'identifier_field_names' => ['id'],
                'fields'                 => [
                    'id' => ['data_type' => 'integer']
                ]
            ]));

        $result = $this->customAssociationCompleter->completeCustomDataType(
            $this->getClassMetadataMock(self::TEST_CLASS_NAME),
            $config,
            $associationName,
            $config->getField($associationName),
            $dataType,
            $version,
            $requestType
        );
        self::assertTrue($result);

        $this->assertConfig(
            [
                'fields' => [
                    $associationName => [
                        'data_type'              => $dataType,
                        'target_class'           => EntityIdentifier::class,
                        'target_type'            => 'to-many',
                        'depends_on'             => ['field1'],
                        'exclusion_policy'       => 'all',
                        'identifier_field_names' => ['id'],
                        'collapse'               => true,
                        'fields'                 => [
                            'id' => ['data_type' => 'integer']
                        ]
                    ]
                ]
            ],
            $config
        );
    }

    public function testCompleteExtendedAssociationWithCustomTargetClass(): void
    {
        $dataType = 'association:manyToOne';
        $associationName = 'association1';
        $config = $this->createConfigObject([
            'fields' => [
                $associationName => [
                    'data_type'    => $dataType,
                    'target_class' => 'Test\TargetClass'
                ]
            ]
        ]);
        $version = self::TEST_VERSION;
        $requestType = new RequestType([self::TEST_REQUEST_TYPE]);

        $this->extendedAssociationProvider->expects(self::once())
            ->method('getExtendedAssociationTargets')
            ->with(self::TEST_CLASS_NAME, 'manyToOne', null, $version, $requestType)
            ->willReturn(['Test\TargetClass1' => 'field1']);

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->with('Test\TargetClass', $version, $requestType)
            ->willReturn($this->createRelationConfigObject([
                'identifier_field_names' => ['id'],
                'fields'                 => [
                    'id' => ['data_type' => 'integer']
                ]
            ]));

        $result = $this->customAssociationCompleter->completeCustomDataType(
            $this->getClassMetadataMock(self::TEST_CLASS_NAME),
            $config,
            $associationName,
            $config->getField($associationName),
            $dataType,
            $version,
            $requestType
        );
        self::assertTrue($result);

        $this->assertConfig(
            [
                'fields' => [
                    $associationName => [
                        'exclusion_policy'       => 'all',
                        'data_type'              => $dataType,
                        'target_class'           => 'Test\TargetClass',
                        'target_type'            => 'to-one',
                        'identifier_field_names' => ['id'],
                        'depends_on'             => ['field1'],
                        'collapse'               => true,
                        'fields'                 => [
                            'id' => ['data_type' => 'integer']
                        ]
                    ]
                ]
            ],
            $config
        );
    }

    public function testCompleteExtendedAssociationWithCustomTargetType(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('The "target_type" option cannot be configured for "Test\Class::association1".');

        $dataType = 'association:manyToOne';
        $associationName = 'association1';
        $config = $this->createConfigObject([
            'fields' => [
                $associationName => [
                    'data_type'   => $dataType,
                    'target_type' => 'to-many'
                ]
            ]
        ]);
        $version = self::TEST_VERSION;
        $requestType = new RequestType([self::TEST_REQUEST_TYPE]);

        $this->customAssociationCompleter->completeCustomDataType(
            $this->getClassMetadataMock(self::TEST_CLASS_NAME),
            $config,
            $associationName,
            $config->getField($associationName),
            $dataType,
            $version,
            $requestType
        );
    }

    public function testCompleteExtendedAssociationWithCustomDependsOn(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('The "depends_on" option cannot be configured for "Test\Class::association1".');

        $dataType = 'association:manyToOne';
        $associationName = 'association1';
        $config = $this->createConfigObject([
            'fields' => [
                $associationName => [
                    'data_type'  => $dataType,
                    'depends_on' => ['field1']
                ]
            ]
        ]);
        $version = self::TEST_VERSION;
        $requestType = new RequestType([self::TEST_REQUEST_TYPE]);

        $this->customAssociationCompleter->completeCustomDataType(
            $this->getClassMetadataMock(self::TEST_CLASS_NAME),
            $config,
            $associationName,
            $config->getField($associationName),
            $dataType,
            $version,
            $requestType
        );
    }

    public function testCompleteExtendedAssociationWhenTargetsHaveNotStringIdentifier(): void
    {
        $dataType = 'association:manyToOne';
        $associationName = 'association1';
        $config = $this->createConfigObject([
            'fields' => [
                $associationName => ['data_type' => $dataType]
            ]
        ]);
        $version = self::TEST_VERSION;
        $requestType = new RequestType([self::TEST_REQUEST_TYPE]);

        $this->extendedAssociationProvider->expects(self::once())
            ->method('getExtendedAssociationTargets')
            ->with(self::TEST_CLASS_NAME, 'manyToOne', null, $version, $requestType)
            ->willReturn(['Test\TargetClass1' => 'field1', 'Test\TargetClass2' => 'field2']);

        $this->doctrineHelper->expects(self::exactly(2))
            ->method('getEntityMetadataForClass')
            ->willReturnMap([
                ['Test\TargetClass1', true, $this->getClassMetadataWithIdField('Test\TargetClass1', 'integer')],
                ['Test\TargetClass2', true, $this->getClassMetadataWithIdField('Test\TargetClass2', 'integer')]
            ]);

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->with(EntityIdentifier::class, $version, $requestType)
            ->willReturn($this->createRelationConfigObject([
                'identifier_field_names' => ['id'],
                'fields'                 => [
                    'id' => ['data_type' => 'string']
                ]
            ]));

        $result = $this->customAssociationCompleter->completeCustomDataType(
            $this->getClassMetadataMock(self::TEST_CLASS_NAME),
            $config,
            $associationName,
            $config->getField($associationName),
            $dataType,
            $version,
            $requestType
        );
        self::assertTrue($result);

        $this->assertConfig(
            [
                'fields' => [
                    $associationName => [
                        'data_type'              => $dataType,
                        'target_class'           => EntityIdentifier::class,
                        'target_type'            => 'to-one',
                        'depends_on'             => ['field1', 'field2'],
                        'exclusion_policy'       => 'all',
                        'identifier_field_names' => ['id'],
                        'collapse'               => true,
                        'fields'                 => [
                            'id' => ['data_type' => 'integer']
                        ]
                    ]
                ]
            ],
            $config
        );
    }

    public function testCompleteExtendedAssociationWhenTargetsHaveDifferentTypesOfIdentifier(): void
    {
        $dataType = 'association:manyToOne';
        $associationName = 'association1';
        $config = $this->createConfigObject([
            'fields' => [
                $associationName => ['data_type' => $dataType]
            ]
        ]);
        $version = self::TEST_VERSION;
        $requestType = new RequestType([self::TEST_REQUEST_TYPE]);

        $this->extendedAssociationProvider->expects(self::once())
            ->method('getExtendedAssociationTargets')
            ->with(self::TEST_CLASS_NAME, 'manyToOne', null, $version, $requestType)
            ->willReturn(['Test\TargetClass1' => 'field1', 'Test\TargetClass2' => 'field2']);

        $this->doctrineHelper->expects(self::exactly(2))
            ->method('getEntityMetadataForClass')
            ->willReturnMap([
                ['Test\TargetClass1', true, $this->getClassMetadataWithIdField('Test\TargetClass1', 'integer')],
                ['Test\TargetClass2', true, $this->getClassMetadataWithIdField('Test\TargetClass2', 'string')]
            ]);

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->with(EntityIdentifier::class, $version, $requestType)
            ->willReturn($this->createRelationConfigObject([
                'identifier_field_names' => ['id'],
                'fields'                 => [
                    'id' => ['data_type' => 'string']
                ]
            ]));

        $result = $this->customAssociationCompleter->completeCustomDataType(
            $this->getClassMetadataMock(self::TEST_CLASS_NAME),
            $config,
            $associationName,
            $config->getField($associationName),
            $dataType,
            $version,
            $requestType
        );
        self::assertTrue($result);

        $this->assertConfig(
            [
                'fields' => [
                    $associationName => [
                        'data_type'              => $dataType,
                        'target_class'           => EntityIdentifier::class,
                        'target_type'            => 'to-one',
                        'depends_on'             => ['field1', 'field2'],
                        'exclusion_policy'       => 'all',
                        'identifier_field_names' => ['id'],
                        'collapse'               => true,
                        'fields'                 => [
                            'id' => ['data_type' => 'string']
                        ]
                    ]
                ]
            ],
            $config
        );
    }

    public function testCompleteExtendedAssociationWhenOneOfTargetHasCompositeIdentifier(): void
    {
        $dataType = 'association:manyToOne';
        $associationName = 'association1';
        $config = $this->createConfigObject([
            'fields' => [
                $associationName => ['data_type' => $dataType]
            ]
        ]);
        $version = self::TEST_VERSION;
        $requestType = new RequestType([self::TEST_REQUEST_TYPE]);

        $this->extendedAssociationProvider->expects(self::once())
            ->method('getExtendedAssociationTargets')
            ->with(self::TEST_CLASS_NAME, 'manyToOne', null, $version, $requestType)
            ->willReturn(['Test\TargetClass1' => 'field1', 'Test\TargetClass2' => 'field2']);

        $target2EntityMetadata = $this->getClassMetadataMock('Test\TargetClass2');
        $target2EntityMetadata->expects(self::once())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id1', 'id2']);
        $target2EntityMetadata->expects(self::never())
            ->method('getTypeOfField');

        $this->doctrineHelper->expects(self::exactly(2))
            ->method('getEntityMetadataForClass')
            ->willReturnMap([
                ['Test\TargetClass1', true, $this->getClassMetadataWithIdField('Test\TargetClass1', 'integer')],
                ['Test\TargetClass2', true, $target2EntityMetadata]
            ]);

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->with(EntityIdentifier::class, $version, $requestType)
            ->willReturn($this->createRelationConfigObject([
                'identifier_field_names' => ['id'],
                'fields'                 => [
                    'id' => ['data_type' => 'string']
                ]
            ]));

        $result = $this->customAssociationCompleter->completeCustomDataType(
            $this->getClassMetadataMock(self::TEST_CLASS_NAME),
            $config,
            $associationName,
            $config->getField($associationName),
            $dataType,
            $version,
            $requestType
        );
        self::assertTrue($result);

        $this->assertConfig(
            [
                'fields' => [
                    $associationName => [
                        'data_type'              => $dataType,
                        'target_class'           => EntityIdentifier::class,
                        'target_type'            => 'to-one',
                        'depends_on'             => ['field1', 'field2'],
                        'exclusion_policy'       => 'all',
                        'identifier_field_names' => ['id'],
                        'collapse'               => true,
                        'fields'                 => [
                            'id' => ['data_type' => 'string']
                        ]
                    ]
                ]
            ],
            $config
        );
    }

    public function testCompleteExtendedAssociationWithEmptyTargets(): void
    {
        $dataType = 'association:manyToOne';
        $associationName = 'association1';
        $config = $this->createConfigObject([
            'fields' => [
                $associationName => ['data_type' => $dataType]
            ]
        ]);
        $version = self::TEST_VERSION;
        $requestType = new RequestType([self::TEST_REQUEST_TYPE]);

        $this->extendedAssociationProvider->expects(self::once())
            ->method('getExtendedAssociationTargets')
            ->with(self::TEST_CLASS_NAME, 'manyToOne', null, $version, $requestType)
            ->willReturn([]);

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->with(EntityIdentifier::class, $version, $requestType)
            ->willReturn($this->createRelationConfigObject([
                'identifier_field_names' => ['id'],
                'fields'                 => [
                    'id' => ['data_type' => 'integer']
                ]
            ]));

        $result = $this->customAssociationCompleter->completeCustomDataType(
            $this->getClassMetadataMock(self::TEST_CLASS_NAME),
            $config,
            $associationName,
            $config->getField($associationName),
            $dataType,
            $version,
            $requestType
        );
        self::assertTrue($result);

        $this->assertConfig(
            [
                'fields' => [
                    $associationName => [
                        'data_type'              => $dataType,
                        'target_class'           => EntityIdentifier::class,
                        'target_type'            => 'to-one',
                        'form_options'           => [
                            'mapped' => false
                        ],
                        'exclusion_policy'       => 'all',
                        'identifier_field_names' => ['id'],
                        'collapse'               => true,
                        'fields'                 => [
                            'id' => ['data_type' => 'integer']
                        ]
                    ]
                ]
            ],
            $config
        );
    }

    public function testCompleteEnumAssociation(): void
    {
        $dataType = 'enum';
        $associationName = 'enumAssociation';
        $fieldName = 'menumField';
        $config = $this->createConfigObject([
            'fields' => [
                $associationName => [
                    'data_type'     => 'string',
                    'target_class'  => 'Extend\Entity\EV_Test_Enum',
                    'target_type'   => 'to-one',
                    'property_path' => $fieldName
                ]
            ]
        ]);
        $version = self::TEST_VERSION;
        $requestType = new RequestType([self::TEST_REQUEST_TYPE]);

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->with('Extend\Entity\EV_Test_Enum', $version, $requestType)
            ->willReturn($this->createRelationConfigObject([
                'identifier_field_names' => ['id'],
                'fields'                 => [
                    'id' => ['data_type' => 'string']
                ]
            ]));

        $qb = $this->createMock(QueryBuilder::class);
        $this->doctrineHelper->expects(self::once())
            ->method('createQueryBuilder')
            ->with(EnumOption::class, 'r')
            ->willReturn($qb);
        $qb->expects(self::once())
            ->method('innerJoin')
            ->with(
                self::TEST_CLASS_NAME,
                'e',
                Join::WITH,
                sprintf(
                    "JSON_EXTRACT(e.serialized_data, '%s') = r.id",
                    $fieldName
                )
            )
            ->willReturnSelf();

        $result = $this->customAssociationCompleter->completeCustomDataType(
            $this->getClassMetadataMock(self::TEST_CLASS_NAME),
            $config,
            $associationName,
            $config->getField($associationName),
            $dataType,
            $version,
            $requestType
        );
        self::assertTrue($result);

        $this->assertConfig(
            [
                'fields' => [
                    $associationName => [
                        'data_type'              => 'string',
                        'target_class'           => 'Extend\Entity\EV_Test_Enum',
                        'target_type'            => 'to-one',
                        'property_path'          => $fieldName,
                        'association_query'      => $qb,
                        'exclusion_policy'       => 'all',
                        'identifier_field_names' => ['id'],
                        'collapse'               => true,
                        'fields'                 => [
                            'id' => ['data_type' => 'string']
                        ]
                    ]
                ]
            ],
            $config
        );
    }

    public function testCompleteMultiEnumAssociation(): void
    {
        $dataType = 'multiEnum';
        $associationName = 'multiEnumAssociation';
        $fieldName = 'multiEnumField';
        $config = $this->createConfigObject([
            'fields' => [
                $associationName => [
                    'data_type'     => 'string',
                    'target_class'  => 'Extend\Entity\EV_Test_Enum',
                    'target_type'   => 'to-many',
                    'property_path' => $fieldName
                ]
            ]
        ]);
        $version = self::TEST_VERSION;
        $requestType = new RequestType([self::TEST_REQUEST_TYPE]);

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->with('Extend\Entity\EV_Test_Enum', $version, $requestType)
            ->willReturn($this->createRelationConfigObject([
                'identifier_field_names' => ['id'],
                'fields'                 => [
                    'id' => ['data_type' => 'string']
                ]
            ]));

        $qb = $this->createMock(QueryBuilder::class);
        $this->doctrineHelper->expects(self::once())
            ->method('createQueryBuilder')
            ->with(EnumOption::class, 'r')
            ->willReturn($qb);
        $qb->expects(self::once())
            ->method('innerJoin')
            ->with(
                self::TEST_CLASS_NAME,
                'e',
                Join::WITH,
                sprintf(
                    "JSONB_ARRAY_CONTAINS_JSON(e.serialized_data, '%s', CONCAT('\"', r.id, '\"')) = true",
                    $fieldName
                )
            )
            ->willReturnSelf();

        $result = $this->customAssociationCompleter->completeCustomDataType(
            $this->getClassMetadataMock(self::TEST_CLASS_NAME),
            $config,
            $associationName,
            $config->getField($associationName),
            $dataType,
            $version,
            $requestType
        );
        self::assertTrue($result);

        $this->assertConfig(
            [
                'fields' => [
                    $associationName => [
                        'data_type'              => 'string',
                        'target_class'           => 'Extend\Entity\EV_Test_Enum',
                        'target_type'            => 'to-many',
                        'property_path'          => $fieldName,
                        'association_query'      => $qb,
                        'exclusion_policy'       => 'all',
                        'identifier_field_names' => ['id'],
                        'collapse'               => true,
                        'fields'                 => [
                            'id' => ['data_type' => 'string']
                        ]
                    ]
                ]
            ],
            $config
        );
    }
}
