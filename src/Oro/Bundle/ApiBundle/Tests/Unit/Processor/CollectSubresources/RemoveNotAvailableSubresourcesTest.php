<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\CollectSubresources;

use Oro\Bundle\ApiBundle\Processor\CollectSubresources\CollectSubresourcesContext;
use Oro\Bundle\ApiBundle\Processor\CollectSubresources\RemoveNotAvailableSubresources;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Request\ApiResource;
use Oro\Bundle\ApiBundle\Request\ApiResourceSubresources;
use Oro\Bundle\ApiBundle\Request\ApiResourceSubresourcesCollection;
use Oro\Bundle\ApiBundle\Request\RequestType;
use PHPUnit\Framework\TestCase;

class RemoveNotAvailableSubresourcesTest extends TestCase
{
    private CollectSubresourcesContext $context;
    private RemoveNotAvailableSubresources $processor;

    #[\Override]
    protected function setUp(): void
    {
        $this->context = new CollectSubresourcesContext();
        $this->context->getRequestType()->add(RequestType::REST);
        $this->context->setVersion('1.1');

        $this->processor = new RemoveNotAvailableSubresources();
    }

    private function getApiResourceSubresources(ApiResource $resource): ApiResourceSubresourcesCollection
    {
        $entitySubresources = new ApiResourceSubresources($resource->getEntityClass());
        $subresources = new ApiResourceSubresourcesCollection();
        $subresources->add($entitySubresources);

        return $subresources;
    }

    public function testSubresourceWithoutExcludedActions(): void
    {
        $entityClass = 'Test\Class';
        $targetEntityClass = 'Test\TargetClass';
        $resource = new ApiResource($entityClass);
        $subresources = $this->getApiResourceSubresources($resource);
        $subresource = $subresources->get($entityClass)->addSubresource('subresource1');
        $subresource->setTargetClassName($targetEntityClass);
        $subresource->setIsCollection(false);

        $this->context->setResources([$resource]);
        $this->context->setResult($subresources);
        $this->processor->process($this->context);

        $expectedSubresources = new ApiResourceSubresources($entityClass);
        $expectedSubresources->addSubresource('subresource1', $subresource);

        self::assertEquals(
            [$entityClass => $expectedSubresources],
            $this->context->getResult()->toArray()
        );
    }

    public function testSubresourceWhenAllActionsAreExcluded(): void
    {
        $entityClass = 'Test\Class';
        $targetEntityClass = 'Test\TargetClass';
        $resource = new ApiResource($entityClass);
        $subresources = $this->getApiResourceSubresources($resource);
        $subresource = $subresources->get($entityClass)->addSubresource('subresource1');
        $subresource->setTargetClassName($targetEntityClass);
        $subresource->setIsCollection(false);
        $subresource->setExcludedActions([
            ApiAction::GET_SUBRESOURCE,
            ApiAction::UPDATE_SUBRESOURCE,
            ApiAction::ADD_SUBRESOURCE,
            ApiAction::DELETE_SUBRESOURCE,
            ApiAction::GET_RELATIONSHIP,
            ApiAction::UPDATE_RELATIONSHIP,
            ApiAction::ADD_RELATIONSHIP,
            ApiAction::DELETE_RELATIONSHIP
        ]);

        $this->context->setResources([$resource]);
        $this->context->setResult($subresources);
        $this->processor->process($this->context);

        self::assertEquals(
            [$entityClass => new ApiResourceSubresources($entityClass)],
            $this->context->getResult()->toArray()
        );
    }

    public function testSubresourceWhenNotAllActionsAreExcluded(): void
    {
        $entityClass = 'Test\Class';
        $targetEntityClass = 'Test\TargetClass';
        $resource = new ApiResource($entityClass);
        $subresources = $this->getApiResourceSubresources($resource);
        $subresource = $subresources->get($entityClass)->addSubresource('subresource1');
        $subresource->setTargetClassName($targetEntityClass);
        $subresource->setIsCollection(false);
        $subresource->setExcludedActions([
            ApiAction::GET_SUBRESOURCE,
            ApiAction::ADD_SUBRESOURCE,
            ApiAction::DELETE_SUBRESOURCE,
            ApiAction::GET_RELATIONSHIP,
            ApiAction::UPDATE_RELATIONSHIP,
            ApiAction::ADD_RELATIONSHIP,
            ApiAction::DELETE_RELATIONSHIP
        ]);

        $this->context->setResources([$resource]);
        $this->context->setResult($subresources);
        $this->processor->process($this->context);

        $expectedSubresources = new ApiResourceSubresources($entityClass);
        $expectedSubresources->addSubresource('subresource1', $subresource);

        self::assertEquals(
            [$entityClass => $expectedSubresources],
            $this->context->getResult()->toArray()
        );
    }
}
