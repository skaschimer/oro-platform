<?php

namespace Oro\Bundle\ApiBundle\Processor\CollectResources;

use Oro\Bundle\ApiBundle\Request\ApiResource;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Collects resources for all custom entities.
 */
class LoadCustomEntities implements ProcessorInterface
{
    private ConfigManager $configManager;

    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var CollectResourcesContext $context */
        $resources = $context->getResult();
        $configs = $this->configManager->getConfigs('extend', null, true);
        foreach ($configs as $config) {
            if ($config->is('is_extend')
                && $config->is('owner', ExtendScope::OWNER_CUSTOM)
                && ExtendHelper::isEntityAccessible($config)
            ) {
                $entityClass = $config->getId()->getClassName();
                if (!$resources->has($entityClass)) {
                    $resources->add(new ApiResource($entityClass));
                }
            }
        }
    }
}
