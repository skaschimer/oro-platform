<?php

namespace Oro\Bundle\MigrationBundle\Tests\Functional\Locator;

use Oro\Bundle\MigrationBundle\Locator\FixturePathLocatorInterface;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class FixturePathLocatorTest extends WebTestCase
{
    private FixturePathLocatorInterface $serviceLocator;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();

        $this->serviceLocator = $this->getContainer()->get('oro_migration.locator.fixture_path_locator');
    }

    public function testGetPathWithDemoType(): void
    {
        $path = $this->serviceLocator->getPath('demo');

        $this->assertEquals($path, 'Migrations/Data/Demo/ORM');
    }

    public function testGetPathWithMainType(): void
    {
        $path = $this->serviceLocator->getPath('main');

        $this->assertEquals($path, 'Migrations/Data/ORM');
    }

    public function testGetPathWithEmptyType(): void
    {
        $path = $this->serviceLocator->getPath('');

        $this->assertEquals($path, 'Migrations/Data/ORM');
    }
}
