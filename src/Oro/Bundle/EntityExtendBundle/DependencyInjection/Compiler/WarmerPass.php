<?php

namespace Oro\Bundle\EntityExtendBundle\DependencyInjection\Compiler;

use Oro\Component\DependencyInjection\ServiceLink;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Replaces "cache_warmer" service with "oro_entity_extend.cache_warmer_aggregate".
 */
class WarmerPass implements CompilerPassInterface
{
    private const string CACHE_WARMER_AGGREGATE_SERVICE = 'oro_entity_extend.cache_warmer_aggregate';
    private const string CACHE_WARMER_SERVICE = 'cache_warmer';
    private const string EXTEND_CACHE_WARMER_SERVICE = 'oro_entity_extend.cache_warmer';
    private const string NEW_CACHE_WARMER_SERVICE = 'oro_entity_extend.cache_warmer.default';
    private const string VALIDATOR_MAPPING_CACHE_SERVICE = 'oro_entity_extend.cache.validator_mapping';
    private const string VALIDATOR_MAPPING_CACHE_ADAPTER = 'validator.mapping.cache.adapter';

    #[\Override]
    public function process(ContainerBuilder $container): void
    {
        $cacheWarmerAggregateDefinition = $container->getDefinition(self::CACHE_WARMER_AGGREGATE_SERVICE);
        $cacheWarmerDefinition = $container->getDefinition(self::CACHE_WARMER_SERVICE);
        $validatorMappingCache = $container->getDefinition(self::VALIDATOR_MAPPING_CACHE_SERVICE);
        $container->removeDefinition(self::CACHE_WARMER_AGGREGATE_SERVICE);
        $container->removeDefinition(self::CACHE_WARMER_SERVICE);
        $container->setDefinition(self::NEW_CACHE_WARMER_SERVICE, $cacheWarmerDefinition);
        $cacheWarmerAggregateDefinition
            ->replaceArgument(0, new Reference($this->addServiceLink($container, self::NEW_CACHE_WARMER_SERVICE)))
            ->replaceArgument(1, new Reference($this->addServiceLink($container, self::EXTEND_CACHE_WARMER_SERVICE)));
        $container->setDefinition(self::CACHE_WARMER_SERVICE, $cacheWarmerAggregateDefinition);
        $container->setDefinition(self::VALIDATOR_MAPPING_CACHE_ADAPTER, $validatorMappingCache);
    }

    private function addServiceLink(ContainerBuilder $container, string $serviceId): string
    {
        $linkServiceId = $serviceId . '.link';
        $linkDefinition = new Definition(
            ServiceLink::class,
            [new Reference('service_container'), $serviceId]
        );
        $linkDefinition->setPublic(false);
        $container->setDefinition($linkServiceId, $linkDefinition);

        return $linkServiceId;
    }
}
