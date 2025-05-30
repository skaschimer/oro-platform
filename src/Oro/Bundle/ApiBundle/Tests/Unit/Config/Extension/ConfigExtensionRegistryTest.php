<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Config\Extension;

use Oro\Bundle\ApiBundle\Config\Definition\ConfigurationSectionInterface;
use Oro\Bundle\ApiBundle\Config\Definition\ConfigurationSettings;
use Oro\Bundle\ApiBundle\Config\Extension\ConfigExtensionInterface;
use Oro\Bundle\ApiBundle\Config\Extension\ConfigExtensionRegistry;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

class ConfigExtensionRegistryTest extends TestCase
{
    public function testDefaultConstructor(): void
    {
        $configExtensionRegistry = new ConfigExtensionRegistry();
        self::assertSame(0, $configExtensionRegistry->getMaxNestingLevel());
    }

    public function testConstructorWithMaxNestingLevel(): void
    {
        $configExtensionRegistry = new ConfigExtensionRegistry(1);
        self::assertSame(1, $configExtensionRegistry->getMaxNestingLevel());
    }

    public function testExtensions(): void
    {
        $configExtensionRegistry = new ConfigExtensionRegistry();
        self::assertSame([], $configExtensionRegistry->getExtensions());

        $extension = $this->createMock(ConfigExtensionInterface::class);
        $configExtensionRegistry->addExtension($extension);
        self::assertSame([$extension], $configExtensionRegistry->getExtensions());
    }

    public function testGetConfigurationSettings(): void
    {
        $configExtensionRegistry = new ConfigExtensionRegistry();
        $extension = $this->createMock(ConfigExtensionInterface::class);
        $configExtensionRegistry->addExtension($extension);

        $section1 = $this->createMock(ConfigurationSectionInterface::class);
        $configureCallback = function (NodeBuilder $node) {
        };
        $preProcessCallback = function ($config) {
        };
        $postProcessCallback = function ($config) {
        };

        $extension->expects(self::once())
            ->method('getEntityConfigurationSections')
            ->willReturn(['section1' => $section1]);
        $extension->expects(self::once())
            ->method('getConfigureCallbacks')
            ->willReturn(['section1' => $configureCallback]);
        $extension->expects(self::once())
            ->method('getPreProcessCallbacks')
            ->willReturn(['section1' => $preProcessCallback]);
        $extension->expects(self::once())
            ->method('getPostProcessCallbacks')
            ->willReturn(['section1' => $postProcessCallback]);

        $section1->expects(self::once())
            ->method('setSettings')
            ->with(self::isInstanceOf(ConfigurationSettings::class));

        $settings = $configExtensionRegistry->getConfigurationSettings();

        $extraSections = $settings->getExtraSections();
        self::assertCount(1, $extraSections);
        self::assertSame($section1, $extraSections['section1']);

        $configureCallbacks = $settings->getConfigureCallbacks('section1');
        self::assertCount(1, $configureCallbacks);
        self::assertSame($configureCallback, reset($configureCallbacks));

        $preProcessCallbacks = $settings->getPreProcessCallbacks('section1');
        self::assertCount(1, $preProcessCallbacks);
        self::assertSame($preProcessCallback, reset($preProcessCallbacks));

        $postProcessCallbacks = $settings->getPostProcessCallbacks('section1');
        self::assertCount(1, $postProcessCallbacks);
        self::assertSame($postProcessCallback, reset($postProcessCallbacks));
    }

    public function testGetConfigSectionNames(): void
    {
        $configExtensionRegistry = new ConfigExtensionRegistry();
        $extension1 = $this->createMock(ConfigExtensionInterface::class);
        $extension2 = $this->createMock(ConfigExtensionInterface::class);
        $configExtensionRegistry->addExtension($extension1);
        $configExtensionRegistry->addExtension($extension2);

        $extension1->expects(self::once())
            ->method('getEntityConfigurationSections')
            ->willReturn(['section1' => null, 'section2' => null]);
        $extension2->expects(self::once())
            ->method('getEntityConfigurationSections')
            ->willReturn(['section2' => null, 'section3' => null]);

        self::assertEquals(
            ['section1', 'section2', 'section3'],
            $configExtensionRegistry->getConfigSectionNames()
        );
    }
}
