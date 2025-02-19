<?php

namespace Oro\Bundle\ConfigBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\ConfigBundle\Entity\ConfigValue;
use Oro\Bundle\ConfigBundle\Entity\Repository\ConfigValueRepository;
use Oro\Bundle\ConfigBundle\Tests\Functional\DataFixtures\LoadConfigValue;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 */
class ConfigValueRepositoryTest extends WebTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadConfigValue::class]);
    }

    private function getRepository(): ConfigValueRepository
    {
        return self::getContainer()->get('doctrine')->getRepository(ConfigValue::class);
    }

    public function testRemoveSection(): void
    {
        $section = 'general';

        $configValues = $this->getRepository()->findBy(['section' => $section]);

        self::assertNotEmpty($configValues);

        $this->getRepository()->removeBySection($section);

        $configValues = $this->getRepository()->findBy(['section' => $section]);

        self::assertEmpty($configValues);
    }

    public function testGetConfigValues(): void
    {
        /** @var ConfigValue $configValue */
        $configValue = $this->getRepository()->findOneBy(['section' => 'additional']);
        $result = $this->getRepository()->getConfigValues(
            $configValue->getConfig()->getScopedEntity(),
            $configValue->getSection(),
            $configValue->getName()
        );

        self::assertEquals([$configValue], $result);
    }

    public function testGetConfigValueRecordIds(): void
    {
        /** @var ConfigValue $configValue */
        $configValue = $this->getRepository()->findOneBy(['section' => 'additional']);

        $result = $this->getRepository()->getConfigValueRecordIds(
            $configValue->getConfig()->getScopedEntity(),
            $configValue->getSection(),
            $configValue->getName()
        );

        self::assertEquals([$configValue->getConfig()->getRecordId()], $result);
    }

    public function getConfigValueByRecordId(): void
    {
        /** @var ConfigValue $configValue */
        $configValue = $this->getRepository()->findOneBy([
            'section' => 'additional',
            'name' => 'additional_section_scalar_value'
        ]);

        $result = $this->getRepository()->getConfigValueByRecordId(
            $configValue->getConfig()->getRecordId(),
            $configValue->getSection(),
            $configValue->getName()
        );

        self::assertEquals($configValue, $result);
    }
}
