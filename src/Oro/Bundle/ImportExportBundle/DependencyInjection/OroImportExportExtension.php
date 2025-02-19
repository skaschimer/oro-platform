<?php

namespace Oro\Bundle\ImportExportBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class OroImportExportExtension extends Extension
{
    #[\Override]
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('serializer.yml');
        $loader->load('context.yml');
        $loader->load('converter.yml');
        $loader->load('strategy.yml');
        $loader->load('reader.yml');
        $loader->load('writer.yml');
        $loader->load('processor.yml');
        $loader->load('form_types.yml');
        $loader->load('executor.yml');
        $loader->load('handler.yml');
        $loader->load('field.yml');
        $loader->load('services.yml');
        $loader->load('mq_processor.yml');
        $loader->load('commands.yml');
        $loader->load('controllers.yml');
        $loader->load('mq_topics.yml');

        if ('test' === $container->getParameter('kernel.environment')) {
            $loader->load('services_test.yml');
        }
    }
}
