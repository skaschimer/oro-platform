<?php

namespace Oro\Bundle\EntityConfigBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class OroEntityConfigExtension extends Extension
{
    #[\Override]
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');
        $loader->load('form_type.yml');
        $loader->load('importexport.yml');
        $loader->load('block_types.yml');
        $loader->load('attribute_types.yml');
        $loader->load('commands.yml');
        $loader->load('controllers.yml');
        $loader->load('mq_topics.yml');

        if ('test' === $container->getParameter('kernel.environment')) {
            $loader->load('services_test.yml');
        }
    }
}
