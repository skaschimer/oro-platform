<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Owner;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataInterface;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProviderInterface;
use Oro\Bundle\SecurityBundle\Owner\OwnershipQueryHelper;
use Oro\Bundle\SecurityBundle\Tests\Unit\Owner\Fixtures\Entity;
use Oro\Component\Testing\Unit\ORM\OrmTestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class OwnershipQueryHelperTest extends OrmTestCase
{
    /** @var EntityManagerInterface */
    private $em;

    /** @var OwnershipMetadataProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $ownershipMetadataProvider;

    /** @var OwnershipQueryHelper */
    private $ownershipQueryHelper;

    #[\Override]
    protected function setUp(): void
    {
        $this->em = $this->getTestEntityManager();
        $this->em->getConfiguration()->setMetadataDriverImpl(new AttributeDriver([]));

        $this->ownershipMetadataProvider = $this->createMock(OwnershipMetadataProviderInterface::class);

        $entityClassResolver = $this->createMock(EntityClassResolver::class);
        $entityClassResolver->expects(self::any())
            ->method('getEntityClass')
            ->willReturnCallback(function ($entityName) {
                return str_starts_with($entityName, 'Test:')
                    ? 'Oro\Bundle\SecurityBundle\Tests\Unit\Owner\Fixtures\Entity' . substr($entityName, 5)
                    : $entityName;
            });

        $this->ownershipQueryHelper = new OwnershipQueryHelper(
            $this->ownershipMetadataProvider,
            $entityClassResolver
        );
    }

    private function getOwnershipMetadata(
        string $organizationFieldName,
        string $ownerFieldName
    ): OwnershipMetadataInterface {
        $metadata = $this->createMock(OwnershipMetadataInterface::class);
        $metadata->expects(self::any())
            ->method('hasOwner')
            ->willReturn($organizationFieldName || $ownerFieldName);
        $metadata->expects(self::any())
            ->method('getOrganizationFieldName')
            ->willReturn($organizationFieldName);
        $metadata->expects(self::any())
            ->method('getOwnerFieldName')
            ->willReturn($ownerFieldName);

        return $metadata;
    }

    public function testNoIdFieldInSelect()
    {
        $entityClass = Entity\TestOwnershipEntity::class;

        $qb = $this->em->createQueryBuilder()
            ->from($entityClass, 'e')
            ->select('e.name');

        $this->ownershipMetadataProvider->expects(self::never())
            ->method('getMetadata')
            ->with($entityClass);

        $result = $this->ownershipQueryHelper->addOwnershipFields($qb);
        self::assertEquals(
            [],
            $result
        );
        self::assertEquals(
            'SELECT e.name FROM ' . $entityClass . ' e',
            $qb->getDQL()
        );
    }

    public function testIdFieldWithExprInSelect()
    {
        $entityClass = Entity\TestOwnershipEntity::class;

        $qb = $this->em->createQueryBuilder()
            ->from($entityClass, 'e')
            ->select('e.id + 1 AS id');

        $this->ownershipMetadataProvider->expects(self::never())
            ->method('getMetadata')
            ->with($entityClass);

        $result = $this->ownershipQueryHelper->addOwnershipFields($qb);
        self::assertEquals(
            [],
            $result
        );
        self::assertEquals(
            'SELECT e.id + 1 AS id FROM ' . $entityClass . ' e',
            $qb->getDQL()
        );
    }

    public function testIdFieldWithFunctionInSelect()
    {
        $entityClass = Entity\TestOwnershipEntity::class;

        $qb = $this->em->createQueryBuilder()
            ->from($entityClass, 'e')
            ->select('NULLIF(e.id, e.id1) AS id');

        $this->ownershipMetadataProvider->expects(self::never())
            ->method('getMetadata')
            ->with($entityClass);

        $result = $this->ownershipQueryHelper->addOwnershipFields($qb);
        self::assertEquals(
            [],
            $result
        );
        self::assertEquals(
            'SELECT NULLIF(e.id, e.id1) AS id FROM ' . $entityClass . ' e',
            $qb->getDQL()
        );
    }

    public function testIdWithoutAliasInSelect()
    {
        $entityClass = Entity\TestOwnershipEntity::class;

        $qb = $this->em->createQueryBuilder()
            ->from($entityClass, 'e')
            ->select('e.id');

        $this->ownershipMetadataProvider->expects(self::once())
            ->method('getMetadata')
            ->with($entityClass)
            ->willReturn($this->getOwnershipMetadata('organization', 'owner'));

        $result = $this->ownershipQueryHelper->addOwnershipFields($qb);
        self::assertEquals(
            [
                'e' => [$entityClass, 'id', 'e_organization_id', 'e_owner_id']
            ],
            $result
        );
        self::assertEquals(
            'SELECT e.id,'
            . ' IDENTITY(e.organization) AS e_organization_id,'
            . ' IDENTITY(e.owner) AS e_owner_id'
            . ' FROM ' . $entityClass . ' e',
            $qb->getDQL()
        );
    }

    public function testIdWithAliasInSelect()
    {
        $entityClass = Entity\TestOwnershipEntity::class;

        $qb = $this->em->createQueryBuilder()
            ->from($entityClass, 'e')
            ->select('e.id  AS  idAlias');

        $this->ownershipMetadataProvider->expects(self::once())
            ->method('getMetadata')
            ->with($entityClass)
            ->willReturn($this->getOwnershipMetadata('organization', 'owner'));

        $result = $this->ownershipQueryHelper->addOwnershipFields($qb);
        self::assertEquals(
            [
                'e' => [$entityClass, 'idAlias', 'e_organization_id', 'e_owner_id']
            ],
            $result
        );
        self::assertEquals(
            'SELECT e.id  AS  idAlias,'
            . ' IDENTITY(e.organization) AS e_organization_id,'
            . ' IDENTITY(e.owner) AS e_owner_id'
            . ' FROM ' . $entityClass . ' e',
            $qb->getDQL()
        );
    }

    public function testIdWithoutAliasAsFirstFieldInSelect()
    {
        $entityClass = Entity\TestOwnershipEntity::class;

        $qb = $this->em->createQueryBuilder()
            ->from($entityClass, 'e')
            ->select('e.id, e.name');

        $this->ownershipMetadataProvider->expects(self::once())
            ->method('getMetadata')
            ->with($entityClass)
            ->willReturn($this->getOwnershipMetadata('organization', 'owner'));

        $result = $this->ownershipQueryHelper->addOwnershipFields($qb);
        self::assertEquals(
            [
                'e' => [$entityClass, 'id', 'e_organization_id', 'e_owner_id']
            ],
            $result
        );
        self::assertEquals(
            'SELECT e.id, e.name,'
            . ' IDENTITY(e.organization) AS e_organization_id,'
            . ' IDENTITY(e.owner) AS e_owner_id'
            . ' FROM ' . $entityClass . ' e',
            $qb->getDQL()
        );
    }

    public function testIdWithAliasAsFirstFieldInSelect()
    {
        $entityClass = Entity\TestOwnershipEntity::class;

        $qb = $this->em->createQueryBuilder()
            ->from($entityClass, 'e')
            ->select('e.id AS id, e.name');

        $this->ownershipMetadataProvider->expects(self::once())
            ->method('getMetadata')
            ->with($entityClass)
            ->willReturn($this->getOwnershipMetadata('organization', 'owner'));

        $result = $this->ownershipQueryHelper->addOwnershipFields($qb);
        self::assertEquals(
            [
                'e' => [$entityClass, 'id', 'e_organization_id', 'e_owner_id']
            ],
            $result
        );
        self::assertEquals(
            'SELECT e.id AS id, e.name,'
            . ' IDENTITY(e.organization) AS e_organization_id,'
            . ' IDENTITY(e.owner) AS e_owner_id'
            . ' FROM ' . $entityClass . ' e',
            $qb->getDQL()
        );
    }

    public function testIdWithoutAliasAsLastFieldInSelect()
    {
        $entityClass = Entity\TestOwnershipEntity::class;

        $qb = $this->em->createQueryBuilder()
            ->from($entityClass, 'e')
            ->select('e.name, e.id');

        $this->ownershipMetadataProvider->expects(self::once())
            ->method('getMetadata')
            ->with($entityClass)
            ->willReturn($this->getOwnershipMetadata('organization', 'owner'));

        $result = $this->ownershipQueryHelper->addOwnershipFields($qb);
        self::assertEquals(
            [
                'e' => [$entityClass, 'id', 'e_organization_id', 'e_owner_id']
            ],
            $result
        );
        self::assertEquals(
            'SELECT e.name, e.id,'
            . ' IDENTITY(e.organization) AS e_organization_id,'
            . ' IDENTITY(e.owner) AS e_owner_id'
            . ' FROM ' . $entityClass . ' e',
            $qb->getDQL()
        );
    }

    public function testIdWithAliasAsLastFieldInSelect()
    {
        $entityClass = Entity\TestOwnershipEntity::class;

        $qb = $this->em->createQueryBuilder()
            ->from($entityClass, 'e')
            ->select('e.name, e.id AS id');

        $this->ownershipMetadataProvider->expects(self::once())
            ->method('getMetadata')
            ->with($entityClass)
            ->willReturn($this->getOwnershipMetadata('organization', 'owner'));

        $result = $this->ownershipQueryHelper->addOwnershipFields($qb);
        self::assertEquals(
            [
                'e' => [$entityClass, 'id', 'e_organization_id', 'e_owner_id']
            ],
            $result
        );
        self::assertEquals(
            'SELECT e.name, e.id AS id,'
            . ' IDENTITY(e.organization) AS e_organization_id,'
            . ' IDENTITY(e.owner) AS e_owner_id'
            . ' FROM ' . $entityClass . ' e',
            $qb->getDQL()
        );
    }

    public function testIdWithoutAliasAsMiddleFieldInSelect()
    {
        $entityClass = Entity\TestOwnershipEntity::class;

        $qb = $this->em->createQueryBuilder()
            ->from($entityClass, 'e')
            ->select('e.name, e.id, e.name AS name1');

        $this->ownershipMetadataProvider->expects(self::once())
            ->method('getMetadata')
            ->with($entityClass)
            ->willReturn($this->getOwnershipMetadata('organization', 'owner'));

        $result = $this->ownershipQueryHelper->addOwnershipFields($qb);
        self::assertEquals(
            [
                'e' => [$entityClass, 'id', 'e_organization_id', 'e_owner_id']
            ],
            $result
        );
        self::assertEquals(
            'SELECT e.name, e.id, e.name AS name1,'
            . ' IDENTITY(e.organization) AS e_organization_id,'
            . ' IDENTITY(e.owner) AS e_owner_id'
            . ' FROM ' . $entityClass . ' e',
            $qb->getDQL()
        );
    }

    public function testIdWithAliasAsMiddleFieldInSelect()
    {
        $entityClass = Entity\TestOwnershipEntity::class;

        $qb = $this->em->createQueryBuilder()
            ->from($entityClass, 'e')
            ->select('e.name, e.id AS id, e.name AS name1');

        $this->ownershipMetadataProvider->expects(self::once())
            ->method('getMetadata')
            ->with($entityClass)
            ->willReturn($this->getOwnershipMetadata('organization', 'owner'));

        $result = $this->ownershipQueryHelper->addOwnershipFields($qb);
        self::assertEquals(
            [
                'e' => [$entityClass, 'id', 'e_organization_id', 'e_owner_id']
            ],
            $result
        );
        self::assertEquals(
            'SELECT e.name, e.id AS id, e.name AS name1,'
            . ' IDENTITY(e.organization) AS e_organization_id,'
            . ' IDENTITY(e.owner) AS e_owner_id'
            . ' FROM ' . $entityClass . ' e',
            $qb->getDQL()
        );
    }

    public function testFieldStartsWithIdStringAsFirstFieldInSelect()
    {
        $entityClass = Entity\TestOwnershipEntity::class;

        $qb = $this->em->createQueryBuilder()
            ->from($entityClass, 'e')
            ->select('e.id1, e.id');

        $this->ownershipMetadataProvider->expects(self::once())
            ->method('getMetadata')
            ->with($entityClass)
            ->willReturn($this->getOwnershipMetadata('organization', 'owner'));

        $result = $this->ownershipQueryHelper->addOwnershipFields($qb);
        self::assertEquals(
            [
                'e' => [$entityClass, 'id', 'e_organization_id', 'e_owner_id']
            ],
            $result
        );
        self::assertEquals(
            'SELECT e.id1, e.id,'
            . ' IDENTITY(e.organization) AS e_organization_id,'
            . ' IDENTITY(e.owner) AS e_owner_id'
            . ' FROM ' . $entityClass . ' e',
            $qb->getDQL()
        );
    }

    public function testFieldStartsWithIdStringAsLastFieldInSelect()
    {
        $entityClass = Entity\TestOwnershipEntity::class;

        $qb = $this->em->createQueryBuilder()
            ->from($entityClass, 'e')
            ->select('e.name, e.id1');

        $this->ownershipMetadataProvider->expects(self::never())
            ->method('getMetadata')
            ->with($entityClass);

        $result = $this->ownershipQueryHelper->addOwnershipFields($qb);
        self::assertEquals(
            [],
            $result
        );
        self::assertEquals(
            'SELECT e.name, e.id1 FROM ' . $entityClass . ' e',
            $qb->getDQL()
        );
    }

    public function testFieldStartsWithIdStringAsMiddleFieldInSelect()
    {
        $entityClass = Entity\TestOwnershipEntity::class;

        $qb = $this->em->createQueryBuilder()
            ->from($entityClass, 'e')
            ->select('e.name, e.id1, e.id');

        $this->ownershipMetadataProvider->expects(self::once())
            ->method('getMetadata')
            ->with($entityClass)
            ->willReturn($this->getOwnershipMetadata('organization', 'owner'));

        $result = $this->ownershipQueryHelper->addOwnershipFields($qb);
        self::assertEquals(
            [
                'e' => [$entityClass, 'id', 'e_organization_id', 'e_owner_id']
            ],
            $result
        );
        self::assertEquals(
            'SELECT e.name, e.id1, e.id,'
            . ' IDENTITY(e.organization) AS e_organization_id,'
            . ' IDENTITY(e.owner) AS e_owner_id'
            . ' FROM ' . $entityClass . ' e',
            $qb->getDQL()
        );
    }

    public function testIdWithAliasInSelectWhenAsIsLowercase()
    {
        $entityClass = Entity\TestOwnershipEntity::class;

        $qb = $this->em->createQueryBuilder()
            ->from($entityClass, 'e')
            ->select('e.id as id');

        $this->ownershipMetadataProvider->expects(self::once())
            ->method('getMetadata')
            ->with($entityClass)
            ->willReturn($this->getOwnershipMetadata('organization', 'owner'));

        $result = $this->ownershipQueryHelper->addOwnershipFields($qb);
        self::assertEquals(
            [
                'e' => [$entityClass, 'id', 'e_organization_id', 'e_owner_id']
            ],
            $result
        );
        self::assertEquals(
            'SELECT e.id as id,'
            . ' IDENTITY(e.organization) AS e_organization_id,'
            . ' IDENTITY(e.owner) AS e_owner_id'
            . ' FROM ' . $entityClass . ' e',
            $qb->getDQL()
        );
    }

    public function testNoOwnerField()
    {
        $entityClass = Entity\TestOwnershipEntity::class;

        $qb = $this->em->createQueryBuilder()
            ->from($entityClass, 'e')
            ->select('e.id');

        $this->ownershipMetadataProvider->expects(self::once())
            ->method('getMetadata')
            ->with($entityClass)
            ->willReturn($this->getOwnershipMetadata('organization', ''));

        $result = $this->ownershipQueryHelper->addOwnershipFields($qb);
        self::assertEquals(
            [
                'e' => [$entityClass, 'id', 'e_organization_id', null]
            ],
            $result
        );
        self::assertEquals(
            'SELECT e.id,'
            . ' IDENTITY(e.organization) AS e_organization_id'
            . ' FROM ' . $entityClass . ' e',
            $qb->getDQL()
        );
    }

    public function testNoOrganizationField()
    {
        $entityClass = Entity\TestOwnershipEntity::class;

        $qb = $this->em->createQueryBuilder()
            ->from($entityClass, 'e')
            ->select('e.id');

        $this->ownershipMetadataProvider->expects(self::once())
            ->method('getMetadata')
            ->with($entityClass)
            ->willReturn($this->getOwnershipMetadata('', 'owner'));

        $result = $this->ownershipQueryHelper->addOwnershipFields($qb);
        self::assertEquals(
            [
                'e' => [$entityClass, 'id', null, 'e_owner_id']
            ],
            $result
        );
        self::assertEquals(
            'SELECT e.id,'
            . ' IDENTITY(e.owner) AS e_owner_id'
            . ' FROM ' . $entityClass . ' e',
            $qb->getDQL()
        );
    }

    public function testEntityWithoutOwnership()
    {
        $entityClass = Entity\TestOwnershipEntity::class;

        $qb = $this->em->createQueryBuilder()
            ->from($entityClass, 'e')
            ->select('e.id');

        $this->ownershipMetadataProvider->expects(self::once())
            ->method('getMetadata')
            ->with($entityClass)
            ->willReturn($this->getOwnershipMetadata('', ''));

        $result = $this->ownershipQueryHelper->addOwnershipFields($qb);
        self::assertEquals(
            [],
            $result
        );
        self::assertEquals(
            'SELECT e.id FROM ' . $entityClass . ' e',
            $qb->getDQL()
        );
    }

    public function testOwnershipFieldsAlreadyExistInSelect()
    {
        $entityClass = Entity\TestOwnershipEntity::class;

        $qb = $this->em->createQueryBuilder()
            ->from($entityClass, 'e')
            ->select('e.id');

        $this->ownershipMetadataProvider->expects(self::exactly(2))
            ->method('getMetadata')
            ->with($entityClass)
            ->willReturn($this->getOwnershipMetadata('organization', 'owner'));

        // add ownership fields
        $this->ownershipQueryHelper->addOwnershipFields($qb);

        // test that ownership fields are not duplicated
        $result = $this->ownershipQueryHelper->addOwnershipFields($qb);
        self::assertEquals(
            [
                'e' => [$entityClass, 'id', 'e_organization_id', 'e_owner_id']
            ],
            $result
        );
        self::assertEquals(
            'SELECT e.id,'
            . ' IDENTITY(e.organization) AS e_organization_id,'
            . ' IDENTITY(e.owner) AS e_owner_id'
            . ' FROM ' . $entityClass . ' e',
            $qb->getDQL()
        );
    }
}
