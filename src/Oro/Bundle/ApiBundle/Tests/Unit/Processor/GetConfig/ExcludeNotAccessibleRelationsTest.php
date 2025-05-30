<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetConfig;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\ApiBundle\Config\Extra\DisabledAssociationsConfigExtra;
use Oro\Bundle\ApiBundle\Model\EntityIdentifier;
use Oro\Bundle\ApiBundle\Processor\GetConfig\ExcludeNotAccessibleRelations;
use Oro\Bundle\ApiBundle\Provider\EntityOverrideProviderInterface;
use Oro\Bundle\ApiBundle\Provider\EntityOverrideProviderRegistry;
use Oro\Bundle\ApiBundle\Provider\ResourcesProvider;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class ExcludeNotAccessibleRelationsTest extends ConfigProcessorTestCase
{
    private const string TARGET_ACTION = 'create';

    private DoctrineHelper&MockObject $doctrineHelper;
    private ResourcesProvider&MockObject $resourcesProvider;
    private EntityOverrideProviderInterface&MockObject $entityOverrideProvider;
    private ExcludeNotAccessibleRelations $processor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->resourcesProvider = $this->createMock(ResourcesProvider::class);
        $this->entityOverrideProvider = $this->createMock(EntityOverrideProviderInterface::class);

        $entityOverrideProviderRegistry = $this->createMock(EntityOverrideProviderRegistry::class);
        $entityOverrideProviderRegistry->expects(self::any())
            ->method('getEntityOverrideProvider')
            ->with($this->context->getRequestType())
            ->willReturn($this->entityOverrideProvider);

        $this->processor = new ExcludeNotAccessibleRelations(
            $this->doctrineHelper,
            $this->resourcesProvider,
            $entityOverrideProviderRegistry
        );

        $this->context->setTargetAction(self::TARGET_ACTION);
        $this->context->setExtras([new DisabledAssociationsConfigExtra()]);
    }

    public function testProcessForNotCompletedConfig(): void
    {
        $config = [
            'fields' => [
                'field1' => null
            ]
        ];

        $this->doctrineHelper->expects(self::never())
            ->method('isManageableEntityClass');

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'fields' => [
                    'field1' => null
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessWhenNoFields(): void
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => []
        ];

        $this->doctrineHelper->expects(self::never())
            ->method('isManageableEntityClass');

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all'
            ],
            $this->context->getResult()
        );
    }

    public function testProcessForNotManageableEntity(): void
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'field1' => null
            ]
        ];

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(false);

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'field1' => null
                ]
            ],
            $this->context->getResult()
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testProcessForManageableEntity(): void
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'field1'       => null,
                'field2'       => [
                    'exclude' => true
                ],
                'association1' => null,
                'association2' => [
                    'exclude' => true
                ],
                'association3' => [
                    'property_path' => 'realAssociation3'
                ],
                'association4' => [
                    'exclude' => false
                ]
            ]
        ];

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects(self::exactly(3))
            ->method('hasAssociation')
            ->willReturnMap([
                ['field1', false],
                ['association1', true],
                ['realAssociation3', true]
            ]);
        $rootEntityMetadata->expects(self::exactly(2))
            ->method('getAssociationMapping')
            ->willReturnMap([
                ['association1', ['targetEntity' => 'Test\Association1Target']],
                ['realAssociation3', ['targetEntity' => 'Test\Association3Target']]
            ]);

        $association1Metadata = $this->getClassMetadataMock('Test\Association1Target');
        $association3Metadata = $this->getClassMetadataMock('Test\Association3Target');

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::exactly(3))
            ->method('getEntityMetadataForClass')
            ->willReturnMap([
                [self::TEST_CLASS_NAME, true, $rootEntityMetadata],
                ['Test\Association1Target', true, $association1Metadata],
                ['Test\Association3Target', true, $association3Metadata]
            ]);
        $this->resourcesProvider->expects(self::exactly(2))
            ->method('isResourceAccessibleAsAssociation')
            ->willReturnMap([
                ['Test\Association1Target', $this->context->getVersion(), $this->context->getRequestType(), true],
                ['Test\Association3Target', $this->context->getVersion(), $this->context->getRequestType(), true]
            ]);

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'field1'       => null,
                    'field2'       => [
                        'exclude' => true
                    ],
                    'association1' => null,
                    'association2' => [
                        'exclude' => true
                    ],
                    'association3' => [
                        'property_path' => 'realAssociation3'
                    ],
                    'association4' => null
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessWhenTargetEntityDoesNotHaveAccessibleApiResource(): void
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'association1' => null
            ]
        ];

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects(self::once())
            ->method('hasAssociation')
            ->with('association1')
            ->willReturn(true);
        $rootEntityMetadata->expects(self::once())
            ->method('getAssociationMapping')
            ->with('association1')
            ->willReturn(['targetEntity' => 'Test\Association1Target']);

        $association1Metadata = $this->getClassMetadataMock('Test\Association1Target');

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::exactly(2))
            ->method('getEntityMetadataForClass')
            ->willReturnMap([
                [self::TEST_CLASS_NAME, true, $rootEntityMetadata],
                ['Test\Association1Target', true, $association1Metadata]
            ]);
        $this->resourcesProvider->expects(self::once())
            ->method('isResourceAccessibleAsAssociation')
            ->with('Test\Association1Target', $this->context->getVersion(), $this->context->getRequestType())
            ->willReturn(false);

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'association1' => [
                        'exclude' => true
                    ]
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessForArrayAssociation(): void
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'association1' => [
                    'data_type' => 'array'
                ]
            ]
        ];

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects(self::once())
            ->method('hasAssociation')
            ->with('association1')
            ->willReturn(true);
        $rootEntityMetadata->expects(self::once())
            ->method('getAssociationMapping')
            ->with('association1')
            ->willReturn(['targetEntity' => 'Test\Association1Target']);

        $association1Metadata = $this->getClassMetadataMock('Test\Association1Target');

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::exactly(2))
            ->method('getEntityMetadataForClass')
            ->willReturnMap([
                [self::TEST_CLASS_NAME, true, $rootEntityMetadata],
                ['Test\Association1Target', true, $association1Metadata]
            ]);
        $this->resourcesProvider->expects(self::once())
            ->method('isResourceKnown')
            ->with('Test\Association1Target', $this->context->getVersion(), $this->context->getRequestType())
            ->willReturn(true);

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'association1' => [
                        'data_type' => 'array'
                    ]
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessForArrayAssociationAndTargetEntityDoesNotHaveApiResource(): void
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'association1' => [
                    'data_type' => 'array'
                ]
            ]
        ];

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects(self::once())
            ->method('hasAssociation')
            ->with('association1')
            ->willReturn(true);
        $rootEntityMetadata->expects(self::once())
            ->method('getAssociationMapping')
            ->with('association1')
            ->willReturn(['targetEntity' => 'Test\Association1Target']);

        $association1Metadata = $this->getClassMetadataMock('Test\Association1Target');

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::exactly(2))
            ->method('getEntityMetadataForClass')
            ->willReturnMap([
                [self::TEST_CLASS_NAME, true, $rootEntityMetadata],
                ['Test\Association1Target', true, $association1Metadata]
            ]);
        $this->resourcesProvider->expects(self::once())
            ->method('isResourceKnown')
            ->with('Test\Association1Target', $this->context->getVersion(), $this->context->getRequestType())
            ->willReturn(false);

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'association1' => [
                        'data_type' => 'array',
                        'exclude'   => true
                    ]
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessWhenTargetEntityUsesTableInheritance(): void
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'association1' => null
            ]
        ];

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects(self::once())
            ->method('hasAssociation')
            ->with('association1')
            ->willReturn(true);
        $rootEntityMetadata->expects(self::once())
            ->method('getAssociationMapping')
            ->with('association1')
            ->willReturn(['targetEntity' => 'Test\Association1Target']);

        $association1Metadata = $this->getClassMetadataMock('Test\Association1Target');
        $association1Metadata->inheritanceType = ClassMetadata::INHERITANCE_TYPE_SINGLE_TABLE;
        $association1Metadata->subClasses = ['Test\Association1Target1'];

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::exactly(2))
            ->method('getEntityMetadataForClass')
            ->willReturnMap([
                [self::TEST_CLASS_NAME, true, $rootEntityMetadata],
                ['Test\Association1Target', true, $association1Metadata]
            ]);
        $this->resourcesProvider->expects(self::exactly(2))
            ->method('isResourceAccessibleAsAssociation')
            ->willReturnMap([
                ['Test\Association1Target', $this->context->getVersion(), $this->context->getRequestType(), false],
                ['Test\Association1Target1', $this->context->getVersion(), $this->context->getRequestType(), true]
            ]);

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'association1' => null
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessForArrayAssociationWhenTargetEntityUsesTableInheritance(): void
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'association1' => [
                    'data_type' => 'array'
                ]
            ]
        ];

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects(self::once())
            ->method('hasAssociation')
            ->with('association1')
            ->willReturn(true);
        $rootEntityMetadata->expects(self::once())
            ->method('getAssociationMapping')
            ->with('association1')
            ->willReturn(['targetEntity' => 'Test\Association1Target']);

        $association1Metadata = $this->getClassMetadataMock('Test\Association1Target');
        $association1Metadata->inheritanceType = ClassMetadata::INHERITANCE_TYPE_SINGLE_TABLE;
        $association1Metadata->subClasses = ['Test\Association1Target1'];

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::exactly(2))
            ->method('getEntityMetadataForClass')
            ->willReturnMap([
                [self::TEST_CLASS_NAME, true, $rootEntityMetadata],
                ['Test\Association1Target', true, $association1Metadata]
            ]);
        $this->resourcesProvider->expects(self::exactly(2))
            ->method('isResourceKnown')
            ->willReturnMap([
                ['Test\Association1Target', $this->context->getVersion(), $this->context->getRequestType(), false],
                ['Test\Association1Target1', $this->context->getVersion(), $this->context->getRequestType(), true]
            ]);

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'association1' => [
                        'data_type' => 'array'
                    ]
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessWhenTargetEntityUsesTableInheritanceAndNoAccessibleApiResourceForAnyTarget(): void
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'association1' => null
            ]
        ];

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects(self::once())
            ->method('hasAssociation')
            ->with('association1')
            ->willReturn(true);
        $rootEntityMetadata->expects(self::once())
            ->method('getAssociationMapping')
            ->with('association1')
            ->willReturn(['targetEntity' => 'Test\Association1Target']);

        $association1Metadata = $this->getClassMetadataMock('Test\Association1Target');
        $association1Metadata->inheritanceType = ClassMetadata::INHERITANCE_TYPE_SINGLE_TABLE;
        $association1Metadata->subClasses = ['Test\Association1Target1'];

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::exactly(2))
            ->method('getEntityMetadataForClass')
            ->willReturnMap([
                [self::TEST_CLASS_NAME, true, $rootEntityMetadata],
                ['Test\Association1Target', true, $association1Metadata]
            ]);
        $this->resourcesProvider->expects(self::exactly(2))
            ->method('isResourceAccessibleAsAssociation')
            ->willReturnMap([
                ['Test\Association1Target', $this->context->getVersion(), $this->context->getRequestType(), false],
                ['Test\Association1Target1', $this->context->getVersion(), $this->context->getRequestType(), false]
            ]);

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'association1' => [
                        'exclude' => true
                    ]
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessForArrayAssociationAndTargetUsesTableInheritanceAndNoApiResourceForAnyTarget(): void
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'association1' => [
                    'data_type' => 'array'
                ]
            ]
        ];

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects(self::once())
            ->method('hasAssociation')
            ->with('association1')
            ->willReturn(true);
        $rootEntityMetadata->expects(self::once())
            ->method('getAssociationMapping')
            ->with('association1')
            ->willReturn(['targetEntity' => 'Test\Association1Target']);

        $association1Metadata = $this->getClassMetadataMock('Test\Association1Target');
        $association1Metadata->inheritanceType = ClassMetadata::INHERITANCE_TYPE_SINGLE_TABLE;
        $association1Metadata->subClasses = ['Test\Association1Target1'];

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::exactly(2))
            ->method('getEntityMetadataForClass')
            ->willReturnMap([
                [self::TEST_CLASS_NAME, true, $rootEntityMetadata],
                ['Test\Association1Target', true, $association1Metadata]
            ]);
        $this->resourcesProvider->expects(self::exactly(2))
            ->method('isResourceKnown')
            ->willReturnMap([
                ['Test\Association1Target', $this->context->getVersion(), $this->context->getRequestType(), false],
                ['Test\Association1Target1', $this->context->getVersion(), $this->context->getRequestType(), false]
            ]);

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'association1' => [
                        'data_type' => 'array',
                        'exclude'   => true
                    ]
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessForAssociationToOverriddenEntity(): void
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'association1' => null
            ]
        ];

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects(self::once())
            ->method('hasAssociation')
            ->with('association1')
            ->willReturn(true);
        $rootEntityMetadata->expects(self::once())
            ->method('getAssociationMapping')
            ->with('association1')
            ->willReturn(['targetEntity' => 'Test\Association1Target']);

        $association1Metadata = $this->getClassMetadataMock('Test\Association1Target');

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::exactly(2))
            ->method('getEntityMetadataForClass')
            ->willReturnMap([
                [self::TEST_CLASS_NAME, true, $rootEntityMetadata],
                ['Test\Association1Target', true, $association1Metadata]
            ]);

        $this->entityOverrideProvider->expects(self::once())
            ->method('getSubstituteEntityClass')
            ->with('Test\Association1Target')
            ->willReturn('Test\Association1SubstituteTarget');
        $this->resourcesProvider->expects(self::once())
            ->method('isResourceAccessibleAsAssociation')
            ->with('Test\Association1SubstituteTarget', $this->context->getVersion(), $this->context->getRequestType())
            ->willReturn(true);

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'association1' => null
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessForArrayAssociationToOverriddenEntity(): void
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'association1' => [
                    'data_type' => 'array'
                ]
            ]
        ];

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects(self::once())
            ->method('hasAssociation')
            ->with('association1')
            ->willReturn(true);
        $rootEntityMetadata->expects(self::once())
            ->method('getAssociationMapping')
            ->with('association1')
            ->willReturn(['targetEntity' => 'Test\Association1Target']);

        $association1Metadata = $this->getClassMetadataMock('Test\Association1Target');

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::exactly(2))
            ->method('getEntityMetadataForClass')
            ->willReturnMap([
                [self::TEST_CLASS_NAME, true, $rootEntityMetadata],
                ['Test\Association1Target', true, $association1Metadata]
            ]);

        $this->entityOverrideProvider->expects(self::once())
            ->method('getSubstituteEntityClass')
            ->with('Test\Association1Target')
            ->willReturn('Test\Association1SubstituteTarget');
        $this->resourcesProvider->expects(self::once())
            ->method('isResourceKnown')
            ->with('Test\Association1SubstituteTarget', $this->context->getVersion(), $this->context->getRequestType())
            ->willReturn(true);

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'association1' => [
                        'data_type' => 'array'
                    ]
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessForAssociationToOverriddenEntityInTableInheritanceSubClass(): void
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'association1' => null
            ]
        ];

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects(self::once())
            ->method('hasAssociation')
            ->with('association1')
            ->willReturn(true);
        $rootEntityMetadata->expects(self::once())
            ->method('getAssociationMapping')
            ->with('association1')
            ->willReturn(['targetEntity' => 'Test\Association1Target']);

        $association1Metadata = $this->getClassMetadataMock('Test\Association1Target');
        $association1Metadata->inheritanceType = ClassMetadata::INHERITANCE_TYPE_SINGLE_TABLE;
        $association1Metadata->subClasses = ['Test\Association1Target1'];

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::exactly(2))
            ->method('getEntityMetadataForClass')
            ->willReturnMap([
                [self::TEST_CLASS_NAME, true, $rootEntityMetadata],
                ['Test\Association1Target', true, $association1Metadata]
            ]);

        $this->entityOverrideProvider->expects(self::exactly(2))
            ->method('getSubstituteEntityClass')
            ->willReturnMap([
                ['Test\Association1Target', null],
                ['Test\Association1Target1', 'Test\Association1SubstituteTarget1']
            ]);
        $this->resourcesProvider->expects(self::exactly(2))
            ->method('isResourceAccessibleAsAssociation')
            ->willReturnMap([
                ['Test\Association1Target', $this->context->getVersion(), $this->context->getRequestType(), false],
                [
                    'Test\Association1SubstituteTarget1',
                    $this->context->getVersion(),
                    $this->context->getRequestType(),
                    true
                ]
            ]);

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'association1' => null
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessForArrayAssociationToOverriddenEntityInTableInheritanceSubClass(): void
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'association1' => [
                    'data_type' => 'array'
                ]
            ]
        ];

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects(self::once())
            ->method('hasAssociation')
            ->with('association1')
            ->willReturn(true);
        $rootEntityMetadata->expects(self::once())
            ->method('getAssociationMapping')
            ->with('association1')
            ->willReturn(['targetEntity' => 'Test\Association1Target']);

        $association1Metadata = $this->getClassMetadataMock('Test\Association1Target');
        $association1Metadata->inheritanceType = ClassMetadata::INHERITANCE_TYPE_SINGLE_TABLE;
        $association1Metadata->subClasses = ['Test\Association1Target1'];

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::exactly(2))
            ->method('getEntityMetadataForClass')
            ->willReturnMap([
                [self::TEST_CLASS_NAME, true, $rootEntityMetadata],
                ['Test\Association1Target', true, $association1Metadata]
            ]);

        $this->entityOverrideProvider->expects(self::exactly(2))
            ->method('getSubstituteEntityClass')
            ->willReturnMap([
                ['Test\Association1Target', null],
                ['Test\Association1Target1', 'Test\Association1SubstituteTarget1']
            ]);
        $this->resourcesProvider->expects(self::exactly(2))
            ->method('isResourceKnown')
            ->willReturnMap([
                ['Test\Association1Target', $this->context->getVersion(), $this->context->getRequestType(), false],
                [
                    'Test\Association1SubstituteTarget1',
                    $this->context->getVersion(),
                    $this->context->getRequestType(),
                    true
                ]
            ]);

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'association1' => [
                        'data_type' => 'array'
                    ]
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessForNotManageableTargetEntity(): void
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'association1' => [
                    'target_class' => 'Test\Association1Target'
                ]
            ]
        ];

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects(self::never())
            ->method('hasAssociation');
        $rootEntityMetadata->expects(self::never())
            ->method('getAssociationMapping');

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME, true)
            ->willReturn($rootEntityMetadata);
        $this->resourcesProvider->expects(self::once())
            ->method('isResourceAccessibleAsAssociation')
            ->with('Test\Association1Target', $this->context->getVersion(), $this->context->getRequestType())
            ->willReturn(true);

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'association1' => [
                        'target_class' => 'Test\Association1Target'
                    ]
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessForNotManageableTargetEntityThatDoesNotHaveAccessibleApiResource(): void
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'association1' => [
                    'target_class' => 'Test\Association1Target'
                ]
            ]
        ];

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects(self::never())
            ->method('hasAssociation');
        $rootEntityMetadata->expects(self::never())
            ->method('getAssociationMapping');

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME, true)
            ->willReturn($rootEntityMetadata);
        $this->resourcesProvider->expects(self::once())
            ->method('isResourceAccessibleAsAssociation')
            ->with('Test\Association1Target', $this->context->getVersion(), $this->context->getRequestType())
            ->willReturn(false);

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'association1' => [
                        'target_class' => 'Test\Association1Target',
                        'exclude'      => true
                    ]
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessForNotManageableTargetEntityWhenSeveralTargetClassesAreAllowed(): void
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'association1' => [
                    'target_class' => EntityIdentifier::class
                ]
            ]
        ];

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects(self::never())
            ->method('hasAssociation');
        $rootEntityMetadata->expects(self::never())
            ->method('getAssociationMapping');

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME, true)
            ->willReturn($rootEntityMetadata);
        $this->resourcesProvider->expects(self::never())
            ->method('isResourceAccessibleAsAssociation');

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'association1' => [
                        'target_class' => EntityIdentifier::class
                    ]
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessForDisabledAssociation(): void
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'association1' => null,
                'association2' => null
            ]
        ];

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects(self::exactly(2))
            ->method('hasAssociation')
            ->willReturnMap([
                ['association1', true],
                ['association2', true]
            ]);
        $rootEntityMetadata->expects(self::exactly(2))
            ->method('getAssociationMapping')
            ->willReturnMap([
                ['association1', ['targetEntity' => 'Test\Association1Target']],
                ['association2', ['targetEntity' => 'Test\Association2Target']],
            ]);

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::exactly(3))
            ->method('getEntityMetadataForClass')
            ->willReturnMap([
                [self::TEST_CLASS_NAME, true, $rootEntityMetadata],
                ['Test\Association1Target', true, $this->getClassMetadataMock('Test\Association1Target')],
                ['Test\Association2Target', true, $this->getClassMetadataMock('Test\Association2Target')]
            ]);
        $this->resourcesProvider->expects(self::exactly(2))
            ->method('isResourceEnabled')
            ->willReturnMap([
                [
                    'Test\Association1Target',
                    self::TARGET_ACTION,
                    $this->context->getVersion(),
                    $this->context->getRequestType(),
                    true
                ],
                [
                    'Test\Association2Target',
                    self::TARGET_ACTION,
                    $this->context->getVersion(),
                    $this->context->getRequestType(),
                    false
                ]
            ]);
        $this->resourcesProvider->expects(self::once())
            ->method('isResourceAccessibleAsAssociation')
            ->with('Test\Association1Target', $this->context->getVersion(), $this->context->getRequestType())
            ->willReturn(true);

        $this->context->removeExtra(DisabledAssociationsConfigExtra::NAME);
        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'association1' => null,
                    'association2' => [
                        'exclude' => true
                    ]
                ]
            ],
            $this->context->getResult()
        );
    }
}
