<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Delete;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\Delete\DeleteEntityByDeleteHandler;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\EntityBundle\Handler\EntityDeleteHandlerInterface;
use Oro\Bundle\EntityBundle\Handler\EntityDeleteHandlerRegistry;
use PHPUnit\Framework\MockObject\MockObject;

class DeleteEntityByDeleteHandlerTest extends DeleteProcessorTestCase
{
    private DoctrineHelper&MockObject $doctrineHelper;

    private EntityDeleteHandlerRegistry&MockObject $deleteHandlerRegistry;
    private DeleteEntityByDeleteHandler $processor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->deleteHandlerRegistry = $this->createMock(EntityDeleteHandlerRegistry::class);

        $this->processor = new DeleteEntityByDeleteHandler(
            $this->doctrineHelper,
            $this->deleteHandlerRegistry
        );
    }

    public function testProcessWithoutResult(): void
    {
        $this->deleteHandlerRegistry->expects(self::never())
            ->method('getHandler');

        $this->processor->process($this->context);
    }

    public function testProcessForNotManageableEntity(): void
    {
        $entity = new \stdClass();
        $entityClass = \get_class($entity);
        $config = new EntityDefinitionConfig();

        $this->doctrineHelper->expects(self::once())
            ->method('getManageableEntityClass')
            ->with($entityClass, $config)
            ->willReturn(null);
        $this->deleteHandlerRegistry->expects(self::never())
            ->method('getHandler');

        $this->context->setClassName($entityClass);
        $this->context->setResult($entity);
        $this->context->setConfig($config);
        $this->processor->process($this->context);

        self::assertSame($entity, $this->context->getResult());
    }

    public function testProcessForNotObjectResult(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('The result property of the context should be an object, "string" given.');

        $entity = 'test';
        $entityClass = 'Test\Entity';
        $config = new EntityDefinitionConfig();
        $deleteHandler = $this->createMock(EntityDeleteHandlerInterface::class);

        $this->doctrineHelper->expects(self::once())
            ->method('getManageableEntityClass')
            ->with($entityClass, $config)
            ->willReturn($entityClass);

        $this->deleteHandlerRegistry->expects(self::once())
            ->method('getHandler')
            ->with($entityClass)
            ->willReturn($deleteHandler);
        $deleteHandler->expects(self::never())
            ->method('delete');

        $this->context->setClassName($entityClass);
        $this->context->setResult($entity);
        $this->context->setConfig($config);
        $this->processor->process($this->context);
    }

    public function testProcess(): void
    {
        $entity = new \stdClass();
        $entityClass = \get_class($entity);
        $config = new EntityDefinitionConfig();
        $deleteHandler = $this->createMock(EntityDeleteHandlerInterface::class);

        $this->doctrineHelper->expects(self::once())
            ->method('getManageableEntityClass')
            ->with($entityClass, $config)
            ->willReturn($entityClass);

        $this->deleteHandlerRegistry->expects(self::once())
            ->method('getHandler')
            ->with($entityClass)
            ->willReturn($deleteHandler);
        $deleteHandler->expects(self::once())
            ->method('delete')
            ->with($entity, self::isTrue())
            ->willReturn(null);

        $this->context->setClassName($entityClass);
        $this->context->setResult($entity);
        $this->context->setConfig($config);
        $this->processor->process($this->context);

        self::assertFalse($this->context->hasResult());
    }

    public function testProcessForModelInheritedFromManageableEntity(): void
    {
        $entity = new \stdClass();
        $entityClass = \get_class($entity);
        $parentEntityClass = 'Test\Parent';
        $config = new EntityDefinitionConfig();
        $config->setParentResourceClass($parentEntityClass);
        $deleteHandler = $this->createMock(EntityDeleteHandlerInterface::class);

        $this->doctrineHelper->expects(self::once())
            ->method('getManageableEntityClass')
            ->with($entityClass, $config)
            ->willReturn($parentEntityClass);

        $this->deleteHandlerRegistry->expects(self::once())
            ->method('getHandler')
            ->with($parentEntityClass)
            ->willReturn($deleteHandler);
        $deleteHandler->expects(self::once())
            ->method('delete')
            ->with($entity, self::isTrue())
            ->willReturn(null);

        $this->context->setClassName($entityClass);
        $this->context->setResult($entity);
        $this->context->setConfig($config);
        $this->processor->process($this->context);

        self::assertFalse($this->context->hasResult());
    }
}
