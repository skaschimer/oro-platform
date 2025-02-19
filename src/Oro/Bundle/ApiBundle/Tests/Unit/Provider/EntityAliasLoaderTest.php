<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Provider;

use Oro\Bundle\ApiBundle\Provider\EntityAliasLoader;
use Oro\Bundle\ApiBundle\Provider\EntityOverrideProviderInterface;
use Oro\Bundle\EntityBundle\Model\EntityAlias;
use Oro\Bundle\EntityBundle\Provider\EntityAliasProviderInterface;
use Oro\Bundle\EntityBundle\Provider\EntityAliasStorage;
use Oro\Bundle\EntityBundle\Provider\EntityClassProviderInterface;

class EntityAliasLoaderTest extends \PHPUnit\Framework\TestCase
{
    /** @var EntityOverrideProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $entityOverrideProvider;

    #[\Override]
    protected function setUp(): void
    {
        $this->entityOverrideProvider = $this->createMock(EntityOverrideProviderInterface::class);
    }

    public function testEmptyLoader()
    {
        $storage = new EntityAliasStorage();
        $loader = new EntityAliasLoader([], [], $this->entityOverrideProvider);
        $loader->load($storage);

        self::assertEquals([], $storage->getAll());
    }

    public function testLoadWhenSeveralProvidersReturnSameClass()
    {
        $classProvider1 = $this->createMock(EntityClassProviderInterface::class);
        $classProvider1->expects(self::once())
            ->method('getClassNames')
            ->willReturn(['Test\Entity1', 'Test\Entity2']);
        $classProvider2 = $this->createMock(EntityClassProviderInterface::class);
        $classProvider2->expects(self::once())
            ->method('getClassNames')
            ->willReturn(['Test\Entity2', 'Test\Entity3']);

        $aliasProvider1 = $this->createMock(EntityAliasProviderInterface::class);
        $aliasProvider1->expects(self::any())
            ->method('getEntityAlias')
            ->willReturnMap([
                ['Test\Entity1', new EntityAlias('alias1', 'plural_alias1')],
                ['Test\Entity2', new EntityAlias('alias2', 'plural_alias2')],
                ['Test\Entity3', new EntityAlias('alias3', 'plural_alias3')]
            ]);

        $storage = new EntityAliasStorage();
        $loader = new EntityAliasLoader(
            [$classProvider1, $classProvider2],
            [$aliasProvider1],
            $this->entityOverrideProvider
        );
        $loader->load($storage);

        self::assertEquals(
            [
                'Test\Entity1' => new EntityAlias('alias1', 'plural_alias1'),
                'Test\Entity2' => new EntityAlias('alias2', 'plural_alias2'),
                'Test\Entity3' => new EntityAlias('alias3', 'plural_alias3')
            ],
            $storage->getAll()
        );
    }

    public function testThatEarlierAliasProviderWins()
    {
        $classProvider1 = $this->createMock(EntityClassProviderInterface::class);
        $classProvider1->expects(self::once())
            ->method('getClassNames')
            ->willReturn(['Test\Entity1']);

        $aliasProvider1 = $this->createMock(EntityAliasProviderInterface::class);
        $aliasProvider1->expects(self::once())
            ->method('getEntityAlias')
            ->with('Test\Entity1')
            ->willReturn(new EntityAlias('alias1', 'plural_alias1'));
        $aliasProvider2 = $this->createMock(EntityAliasProviderInterface::class);
        $aliasProvider2->expects(self::never())
            ->method('getEntityAlias');

        $storage = new EntityAliasStorage();
        $loader = new EntityAliasLoader(
            [$classProvider1],
            [$aliasProvider1, $aliasProvider2],
            $this->entityOverrideProvider
        );
        $loader->load($storage);

        self::assertEquals(
            [
                'Test\Entity1' => new EntityAlias('alias1', 'plural_alias1')
            ],
            $storage->getAll()
        );
    }

    public function testEntityAliasCanBeDisabled()
    {
        $classProvider1 = $this->createMock(EntityClassProviderInterface::class);
        $classProvider1->expects(self::once())
            ->method('getClassNames')
            ->willReturn(['Test\Entity1']);

        $aliasProvider1 = $this->createMock(EntityAliasProviderInterface::class);
        $aliasProvider1->expects(self::once())
            ->method('getEntityAlias')
            ->with('Test\Entity1')
            ->willReturn(false);
        $aliasProvider2 = $this->createMock(EntityAliasProviderInterface::class);
        $aliasProvider2->expects(self::never())
            ->method('getEntityAlias');

        $storage = new EntityAliasStorage();
        $loader = new EntityAliasLoader(
            [$classProvider1],
            [$aliasProvider1, $aliasProvider2],
            $this->entityOverrideProvider
        );
        $loader->load($storage);

        self::assertEquals([], $storage->getAll());
    }

    public function testOverriddenEntityIsNotLoaded()
    {
        $classProvider1 = $this->createMock(EntityClassProviderInterface::class);
        $classProvider1->expects(self::once())
            ->method('getClassNames')
            ->willReturn(['Test\Entity1', 'Test\Entity2', 'Test\Entity3']);

        $aliasProvider1 = $this->createMock(EntityAliasProviderInterface::class);
        $aliasProvider1->expects(self::exactly(2))
            ->method('getEntityAlias')
            ->willReturnMap([
                ['Test\Entity1', new EntityAlias('alias1', 'plural_alias1')],
                ['Test\Entity3', new EntityAlias('alias3', 'plural_alias3')]
            ]);

        $this->entityOverrideProvider->expects(self::exactly(3))
            ->method('getSubstituteEntityClass')
            ->willReturnMap([
                ['Test\Entity1', null],
                ['Test\Entity2', 'Test\Entity3'],
                ['Test\Entity2', null]
            ]);

        $storage = new EntityAliasStorage();
        $loader = new EntityAliasLoader(
            [$classProvider1],
            [$aliasProvider1],
            $this->entityOverrideProvider
        );
        $loader->load($storage);

        self::assertEquals(
            [
                'Test\Entity1' => new EntityAlias('alias1', 'plural_alias1'),
                'Test\Entity3' => new EntityAlias('alias3', 'plural_alias3')
            ],
            $storage->getAll()
        );
    }
}
