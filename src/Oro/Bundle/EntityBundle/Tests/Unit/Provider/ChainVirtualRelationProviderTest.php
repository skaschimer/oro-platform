<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Provider;

use Oro\Bundle\EntityBundle\Provider\ChainVirtualRelationProvider;
use Oro\Bundle\EntityBundle\Provider\VirtualRelationProviderInterface;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ChainVirtualRelationProviderTest extends TestCase
{
    /** @var VirtualRelationProviderInterface[]&MockObject[] */
    private array $providers = [];
    private ConfigProvider&MockObject $configProvider;
    private ChainVirtualRelationProvider $chainProvider;

    #[\Override]
    protected function setUp(): void
    {
        $highPriorityProvider = $this->createMock(VirtualRelationProviderInterface::class);
        $lowPriorityProvider = $this->createMock(VirtualRelationProviderInterface::class);

        $this->providers = [$highPriorityProvider, $lowPriorityProvider];
        $this->configProvider = $this->createMock(ConfigProvider::class);

        $this->chainProvider = new ChainVirtualRelationProvider($this->providers, $this->configProvider);
    }

    public function testIsVirtualRelationByLowPriorityProvider(): void
    {
        $this->providers[0]->expects($this->once())
            ->method('isVirtualRelation')
            ->with('testClass', 'testField')
            ->willReturn(true);
        $this->providers[1]->expects($this->never())
            ->method('isVirtualRelation');

        $this->assertTrue($this->chainProvider->isVirtualRelation('testClass', 'testField'));
    }

    public function testIsVirtualRelationByHighPriorityProvider(): void
    {
        $this->providers[0]->expects($this->once())
            ->method('isVirtualRelation')
            ->with('testClass', 'testField')
            ->willReturn(false);
        $this->providers[1]->expects($this->once())
            ->method('isVirtualRelation')
            ->with('testClass', 'testField')
            ->willReturn(true);

        $this->assertTrue($this->chainProvider->isVirtualRelation('testClass', 'testField'));
    }

    public function testIsVirtualRelationNone(): void
    {
        $this->providers[0]->expects($this->once())
            ->method('isVirtualRelation')
            ->with('testClass', 'testField')
            ->willReturn(false);
        $this->providers[1]->expects($this->once())
            ->method('isVirtualRelation')
            ->with('testClass', 'testField')
            ->willReturn(false);

        $this->assertFalse($this->chainProvider->isVirtualRelation('testClass', 'testField'));
    }

    public function testIsVirtualRelationWithoutChildProviders(): void
    {
        $chainProvider = new ChainVirtualRelationProvider([], $this->configProvider);
        $this->assertFalse($chainProvider->isVirtualRelation('testClass', 'testField'));
    }

    public function testGetVirtualRelations(): void
    {
        $entityClass = 'testClass';

        $this->configProvider->expects($this->once())
            ->method('hasConfig')
            ->with($entityClass)
            ->willReturn(false);
        $this->configProvider->expects($this->never())
            ->method('getConfig');

        $firstRelation = [
            'testField1' => [
                'relation_type' => 'manyToOne',
                'related_entity_name' => 'testClassRelated',
                'query' => [
                    'join' => [
                        'left' => [
                            [
                                'join' => 'testClassRelated',
                                'alias' => 'testAlias',
                                'conditionType' => 'WITH',
                                'condition' => 'testAlias.code = entity.code'
                            ]
                        ]
                    ]
                ]
            ]
        ];
        $secondRelation = [
            'testField2' => [
                'relation_type' => 'manyToOne',
                'related_entity_name' => 'testClassRelated2',
                'query' => [
                    'join' => [
                        'left' => [
                            [
                                'join' => 'testClassRelated2',
                                'alias' => 'testAlias',
                                'conditionType' => 'WITH',
                                'condition' => 'testAlias.code = entity.code'
                            ]
                        ]
                    ]
                ]
            ]
        ];
        $this->providers[0]->expects($this->once())
            ->method('getVirtualRelations')
            ->with($entityClass)
            ->willReturn($firstRelation);
        $this->providers[1]->expects($this->once())
            ->method('getVirtualRelations')
            ->with($entityClass)
            ->willReturn($secondRelation);

        $this->assertEquals(
            array_merge($firstRelation, $secondRelation),
            $this->chainProvider->getVirtualRelations($entityClass)
        );
    }

    public function testGetVirtualRelationsForNotAccessibleEntity(): void
    {
        $entityClass = 'testClass';

        $entityConfig = new Config(
            $this->createMock(ConfigIdInterface::class),
            ['is_extend' => true, 'state' => ExtendScope::STATE_NEW]
        );
        $this->configProvider->expects($this->once())
            ->method('hasConfig')
            ->with($entityClass)
            ->willReturn(true);
        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->with($entityClass)
            ->willReturn($entityConfig);

        $this->providers[0]->expects($this->never())
            ->method('getVirtualRelations');
        $this->providers[1]->expects($this->never())
            ->method('getVirtualRelations');

        $this->assertSame(
            [],
            $this->chainProvider->getVirtualRelations($entityClass)
        );
    }

    public function testGetVirtualRelationsWithoutChildProviders(): void
    {
        $chainProvider = new ChainVirtualRelationProvider([], $this->configProvider);
        $this->assertSame([], $chainProvider->getVirtualRelations('testClass'));
    }

    public function testGetVirtualRelationQuery(): void
    {
        $className = 'stdClass';
        $fieldName = 'testField1';

        $query = [
            'join' => [
                'left' => [
                    [
                        'join' => 'testClassRelated',
                        'alias' => 'testAlias',
                        'conditionType' => 'WITH',
                        'condition' => 'testAlias.code = entity.code'
                    ]
                ]
            ]
        ];

        $this->providers[0]->expects($this->once())
            ->method('isVirtualRelation')
            ->with($className, $fieldName)
            ->willReturn(true);
        $this->providers[0]->expects($this->once())
            ->method('getVirtualRelationQuery')
            ->with($className, $fieldName)
            ->willReturn($query);
        $this->providers[1]->expects($this->never())
            ->method('isVirtualRelation')
            ->with($className, $fieldName);

        $this->assertEquals($query, $this->chainProvider->getVirtualRelationQuery($className, $fieldName));
    }

    public function testGetVirtualRelationQueryException(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('A query for relation "testField" in class "stdClass" was not found.');

        $this->chainProvider->getVirtualRelationQuery('stdClass', 'testField');
    }

    public function testGetVirtualRelationQueryWithoutChildProviders(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('A query for relation "testField" in class "stdClass" was not found.');

        $chainProvider = new ChainVirtualRelationProvider([], $this->configProvider);
        $chainProvider->getVirtualRelationQuery('stdClass', 'testField');
    }
}
