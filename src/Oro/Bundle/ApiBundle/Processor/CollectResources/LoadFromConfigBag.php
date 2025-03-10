<?php

namespace Oro\Bundle\ApiBundle\Processor\CollectResources;

use Oro\Bundle\ApiBundle\Provider\ConfigBagRegistry;
use Oro\Bundle\ApiBundle\Request\ApiResource;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Loads resources for all entities configured in "Resources/config/oro/api.yml".
 */
class LoadFromConfigBag implements ProcessorInterface
{
    private ConfigBagRegistry $configBagRegistry;

    public function __construct(ConfigBagRegistry $configBagRegistry)
    {
        $this->configBagRegistry = $configBagRegistry;
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var CollectResourcesContext $context */

        $resources = $context->getResult();
        $requestType = $context->getRequestType();
        $entityClasses = $this->configBagRegistry
            ->getConfigBag($requestType)
            ->getClassNames($context->getVersion());
        foreach ($entityClasses as $entityClass) {
            if (!$resources->has($entityClass)) {
                $resources->add(new ApiResource($entityClass));
            }
        }
    }
}
