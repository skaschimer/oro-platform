<?php

namespace Oro\Component\Config\Tests\Unit\Loader;

use Oro\Component\Config\CumulativeResource;
use Oro\Component\Config\CumulativeResourceInfo;
use Oro\Component\Config\CumulativeResourceManager;
use Oro\Component\Config\Loader\CumulativeConfigLoader;
use Oro\Component\Config\Loader\CumulativeResourceLoader;
use Oro\Component\Config\Loader\CumulativeResourceLoaderCollection;
use Oro\Component\Config\Loader\YamlCumulativeFileLoader;
use Oro\Component\Config\ResourcesContainer;
use Oro\Component\Config\Tests\Unit\Fixtures\Bundle\TestBundle1\TestBundle1;
use Oro\Component\Config\Tests\Unit\Fixtures\Bundle\TestBundle2\TestBundle2;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class CumulativeConfigLoaderTest extends TestCase
{
    /**
     * @param object $bundle
     *
     * @return string
     */
    private function getBundleDir($bundle)
    {
        return dirname((new \ReflectionClass($bundle))->getFileName());
    }

    /**
     * @param string $path
     *
     * @return string
     */
    private function getPath($path)
    {
        return str_replace('/', DIRECTORY_SEPARATOR, $path);
    }

    public function testConstructorWithEmptyName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('$name must not be empty.');

        new CumulativeConfigLoader('', null);
    }

    public function testConstructorWithNullResourceLoader(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('$resourceLoader must not be empty.');

        new CumulativeConfigLoader('test', null);
    }

    public function testConstructorWithEmptyResourceLoader(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('$resourceLoader must not be empty.');

        new CumulativeConfigLoader('test', []);
    }

    public function testConstructorWithOneResourceLoader(): void
    {
        $this->expectNotToPerformAssertions();

        new CumulativeConfigLoader('test', $this->createMock(CumulativeResourceLoader::class));
    }

    public function testConstructorWithSeveralResourceLoader(): void
    {
        $this->expectNotToPerformAssertions();

        new CumulativeConfigLoader('test', [
            $this->createMock(CumulativeResourceLoader::class),
            $this->createMock(CumulativeResourceLoader::class)
        ]);
    }

    public function testRegisterResources(): void
    {
        $bundle1 = new TestBundle1();
        $bundle1Class = get_class($bundle1);
        $bundle1Dir = $this->getBundleDir($bundle1);
        $bundle2 = new TestBundle2();
        $bundle2Class = get_class($bundle2);

        $resourceLoader1 = new YamlCumulativeFileLoader('Resources/config/test.yml');
        $resourceLoader2 = new YamlCumulativeFileLoader('Resources/config/foo/test.yml');

        CumulativeResourceManager::getInstance()
            ->clear()
            ->setBundles(['TestBundle1' => $bundle1Class, 'TestBundle2' => $bundle2Class]);

        $resourcesContainer = new ResourcesContainer();
        $loader = new CumulativeConfigLoader('test', [$resourceLoader1, $resourceLoader2]);
        $loader->registerResources($resourcesContainer);

        $expectedResource = new CumulativeResource(
            'test',
            new CumulativeResourceLoaderCollection([$resourceLoader1, $resourceLoader2])
        );
        $expectedResource->addFound(
            $bundle1Class,
            $this->getPath($bundle1Dir . '/Resources/config/test.yml')
        );
        $expectedResource->addFound(
            $bundle1Class,
            $this->getPath($bundle1Dir . '/Resources/config/foo/test.yml')
        );

        $this->assertCount(1, $resourcesContainer->getResources());
        $this->assertEquals($expectedResource, $resourcesContainer->getResources()[0]);
    }

    public function testGetResources(): void
    {
        $bundle1 = new TestBundle1();
        $bundle1Class = get_class($bundle1);
        $bundle1Dir = $this->getBundleDir($bundle1);
        $bundle2 = new TestBundle2();
        $bundle2Class = get_class($bundle2);

        $resourceLoader1 = new YamlCumulativeFileLoader('Resources/config/test.yml');
        $resourceLoader2 = new YamlCumulativeFileLoader('Resources/config/foo/test.yml');

        CumulativeResourceManager::getInstance()
            ->clear()
            ->setBundles(['TestBundle1' => $bundle1Class, 'TestBundle2' => $bundle2Class]);

        $loader = new CumulativeConfigLoader('test', [$resourceLoader1, $resourceLoader2]);
        $resource = $loader->getResources();

        $expectedResource = new CumulativeResource(
            'test',
            new CumulativeResourceLoaderCollection([$resourceLoader1, $resourceLoader2])
        );
        $expectedResource->addFound(
            $bundle1Class,
            $this->getPath($bundle1Dir . '/Resources/config/test.yml')
        );
        $expectedResource->addFound(
            $bundle1Class,
            $this->getPath($bundle1Dir . '/Resources/config/foo/test.yml')
        );

        $this->assertEquals($expectedResource, $resource);
    }

    public function testLoad(): void
    {
        $resourceRelativePath = 'Resources/config/test.yml';
        $bundle = new TestBundle1();
        $bundleDir = $this->getBundleDir($bundle);
        $resourceLoader = new YamlCumulativeFileLoader($resourceRelativePath);

        CumulativeResourceManager::getInstance()
            ->clear()
            ->setBundles(['TestBundle1' => get_class($bundle)]);

        $resourcesContainer = new ResourcesContainer();
        $loader = new CumulativeConfigLoader('test', $resourceLoader);
        $result = $loader->load($resourcesContainer);

        $this->assertEquals(
            [
                new CumulativeResourceInfo(
                    get_class($bundle),
                    'test',
                    $this->getPath($bundleDir . '/' . $resourceRelativePath),
                    ['test' => 123]
                )
            ],
            $result
        );

        $expectedResource = new CumulativeResource(
            'test',
            new CumulativeResourceLoaderCollection([$resourceLoader])
        );
        $expectedResource->addFound(
            get_class($bundle),
            $this->getPath($bundleDir . '/' . $resourceRelativePath)
        );
        $this->assertCount(1, $resourcesContainer->getResources());
        $this->assertEquals($expectedResource, $resourcesContainer->getResources()[0]);
    }

    public function testLoadWithAppRootDirectory(): void
    {
        $pathWithoutResources = '/config/test.yml';
        $resourceRelativePath = 'Resources' . $pathWithoutResources;
        $bundle = new TestBundle1();
        $bundleDir = $this->getBundleDir($bundle);
        $appRootDir = realpath($bundleDir . '/../../app');
        $resourceLoader = new YamlCumulativeFileLoader($resourceRelativePath);

        CumulativeResourceManager::getInstance()
            ->clear()
            ->setBundles(['TestBundle1' => get_class($bundle)])
            ->setAppRootDir($appRootDir);

        $resourcesContainer = new ResourcesContainer();
        $loader = new CumulativeConfigLoader('test', $resourceLoader);
        $result = $loader->load($resourcesContainer);

        $this->assertEquals(
            [
                new CumulativeResourceInfo(
                    get_class($bundle),
                    'test',
                    str_replace(
                        '/',
                        DIRECTORY_SEPARATOR,
                        $appRootDir . '/Resources/TestBundle1' . $pathWithoutResources
                    ),
                    ['test' => PHP_INT_MAX]
                )
            ],
            $result
        );
        CumulativeResourceManager::getInstance()->setAppRootDir(null);
    }

    public function testLoadWithoutContainer(): void
    {
        $resourceRelativePath = 'Resources/config/test.yml';
        $bundle = new TestBundle1();
        $bundleDir = $this->getBundleDir($bundle);
        $resourceLoader = new YamlCumulativeFileLoader($resourceRelativePath);

        CumulativeResourceManager::getInstance()
            ->clear()
            ->setBundles(['TestBundle1' => get_class($bundle)]);

        $loader = new CumulativeConfigLoader('test', $resourceLoader);
        $result = $loader->load();

        $this->assertEquals(
            [
                new CumulativeResourceInfo(
                    get_class($bundle),
                    'test',
                    $this->getPath($bundleDir . '/' . $resourceRelativePath),
                    ['test' => 123]
                )
            ],
            $result
        );
    }

    public function testLoadWhenNoResources(): void
    {
        $bundle = new TestBundle1();
        $bundleDir = $this->getBundleDir($bundle);
        $resourceLoader = $this->createMock(CumulativeResourceLoader::class);
        $resourceLoader->expects($this->once())
            ->method('load')
            ->with(get_class($bundle), $bundleDir)
            ->willReturn(null);

        CumulativeResourceManager::getInstance()
            ->clear()
            ->setBundles(['TestBundle1' => get_class($bundle)]);

        $loader = new CumulativeConfigLoader('test', $resourceLoader);
        $result = $loader->load();

        $this->assertCount(0, $result);
    }

    public function testLoadWhenResourceLoaderReturnsArray(): void
    {
        $bundle1 = new TestBundle1();
        $bundle1Dir = $this->getBundleDir($bundle1);
        $bundle2 = new TestBundle2();
        $bundle2Dir = $this->getBundleDir($bundle2);
        $resourceLoader = $this->createMock(CumulativeResourceLoader::class);
        $resource1 = new CumulativeResourceInfo(get_class($bundle1), 'res1', 'res1', []);
        $resource2 = new CumulativeResourceInfo(get_class($bundle1), 'res2', 'res2', []);
        $resource3 = new CumulativeResourceInfo(get_class($bundle2), 'res3', 'res3', []);
        $resourceLoader->expects($this->exactly(2))
            ->method('load')
            ->withConsecutive(
                [get_class($bundle1), $bundle1Dir],
                [get_class($bundle2), $bundle2Dir]
            )
            ->willReturnOnConsecutiveCalls(
                [$resource1, $resource2],
                $resource3
            );

        CumulativeResourceManager::getInstance()
            ->clear()
            ->setBundles(['TestBundle1' => get_class($bundle1), 'TestBundle2' => get_class($bundle2)]);

        $loader = new CumulativeConfigLoader('test', $resourceLoader);
        $result = $loader->load();

        $this->assertEquals([$resource1, $resource2, $resource3], $result);
    }

    public function testLoadEmptyFileWithoutWarnings(): void
    {
        $bundle1 = new TestBundle1();
        $bundle1Dir = $this->getBundleDir($bundle1);

        $resource1 = new CumulativeResourceInfo(get_class($bundle1), 'empty', 'empty', []);

        $resourceLoader = $this->createMock(CumulativeResourceLoader::class);
        $resourceLoader->expects($this->once())
            ->method('load')
            ->with(get_class($bundle1), $bundle1Dir)
            ->willReturn([$resource1]);

        CumulativeResourceManager::getInstance()
            ->clear()
            ->setBundles(['TestBundle1' => get_class($bundle1)]);

        $loader = new CumulativeConfigLoader('empty', $resourceLoader);
        $result = $loader->load();

        $this->assertEquals([$resource1], $result);
    }

    public function testYamlCumulativeFileLoaderImports(): void
    {
        $bundle1 = new TestBundle1();
        $bundleClass = get_class($bundle1);
        $bundleDir = $this->getBundleDir($bundle1);

        $resourceLoader1 = new YamlCumulativeFileLoader('Resources/config/datagrid/success/parent.yml');
        $resource = $resourceLoader1->load($bundleClass, $bundleDir);

        $this->assertArrayNotHasKey('imports', $resource->data); // import must be transparent
        $this->assertArrayHasKey('test', $resource->data);
        $this->assertEquals($resource->data['test'], 'success');
    }

    public function testYamlCumulativeFileLoaderImportsInfiniteLoop(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Circular import detected');

        $bundle1 = new TestBundle1();
        $bundleClass = get_class($bundle1);
        $bundleDir = $this->getBundleDir($bundle1);

        $resourceLoader1 = new YamlCumulativeFileLoader('Resources/config/datagrid/loop/parent.yml');
        $resourceLoader1->load($bundleClass, $bundleDir);
    }
}
