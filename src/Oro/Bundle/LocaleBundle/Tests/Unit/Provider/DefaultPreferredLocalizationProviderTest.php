<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Provider;

use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Manager\LocalizationManager;
use Oro\Bundle\LocaleBundle\Provider\DefaultPreferredLocalizationProvider;
use Oro\Bundle\UserBundle\Entity\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DefaultPreferredLocalizationProviderTest extends TestCase
{
    private LocalizationManager&MockObject $localizationManager;
    private DefaultPreferredLocalizationProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->localizationManager = $this->createMock(LocalizationManager::class);

        $this->provider = new DefaultPreferredLocalizationProvider($this->localizationManager);
    }

    /**
     * @dataProvider entityDataProvider
     *
     * @param mixed $entity
     */
    public function testSupports($entity): void
    {
        $this->assertTrue($this->provider->supports($entity));
    }

    /**
     * @dataProvider entityDataProvider
     *
     * @param mixed $entity
     */
    public function testGetPreferredLocalization($entity): void
    {
        $localization = new Localization();
        $this->localizationManager->expects($this->atLeastOnce())
            ->method('getDefaultLocalization')
            ->willReturn($localization);

        $this->assertSame($localization, $this->provider->getPreferredLocalization($entity));
    }

    public function entityDataProvider(): array
    {
        return [
            [new User()],
            [new \stdClass()],
            [false],
            [null],
        ];
    }
}
