<?php

namespace Oro\Bundle\ApiBundle\Processor\CollectResources;

use Oro\Bundle\ApiBundle\Provider\ExclusionProviderRegistry;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Removes resources for excluded entities.
 */
class RemoveExcludedEntities implements ProcessorInterface
{
    private ExclusionProviderRegistry $exclusionProviderRegistry;

    public function __construct(ExclusionProviderRegistry $exclusionProviderRegistry)
    {
        $this->exclusionProviderRegistry = $exclusionProviderRegistry;
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var CollectResourcesContext $context */

        $resources = $context->getResult();
        $entityClasses = array_keys($resources->toArray());
        $exclusionProvider = $this->exclusionProviderRegistry->getExclusionProvider($context->getRequestType());
        foreach ($entityClasses as $entityClass) {
            if ($exclusionProvider->isIgnoredEntity($entityClass)) {
                $resources->remove($entityClass);
            }
        }
    }
}
