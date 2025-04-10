<?php

namespace Oro\Bundle\WorkflowBundle\Configuration;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Processor;

class ProcessTriggerListConfiguration implements ConfigurationInterface
{
    /**
     * @var ProcessTriggerConfiguration
     */
    protected $triggerConfiguration;

    public function __construct(ProcessTriggerConfiguration $triggerConfiguration)
    {
        $this->triggerConfiguration = $triggerConfiguration;
    }

    /**
     * @param array $configs
     * @return array
     */
    public function processConfiguration(array $configs)
    {
        $processor = new Processor();
        return $processor->processConfiguration($this, array($configs));
    }

    #[\Override]
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('configuration');
        $rootNode = $treeBuilder->getRootNode();
        $rootNode->useAttributeAsKey('name');
        $this->triggerConfiguration->addTriggerNodes($rootNode->prototype('array')->prototype('array'));

        return $treeBuilder;
    }
}
