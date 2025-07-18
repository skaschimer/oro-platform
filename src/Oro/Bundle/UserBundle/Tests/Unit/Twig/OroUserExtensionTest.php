<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Twig;

use Oro\Bundle\UserBundle\Model\Gender;
use Oro\Bundle\UserBundle\Provider\GenderProvider;
use Oro\Bundle\UserBundle\Twig\OroUserExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class OroUserExtensionTest extends TestCase
{
    use TwigExtensionTestCaseTrait;

    private GenderProvider&MockObject $genderProvider;
    private OroUserExtension $extension;

    #[\Override]
    protected function setUp(): void
    {
        $this->genderProvider = $this->createMock(GenderProvider::class);

        $container = self::getContainerBuilder()
            ->add('oro_user.gender_provider', $this->genderProvider)
            ->getContainer($this);

        $this->extension = new OroUserExtension($container);
    }

    public function testGetGenderLabel(): void
    {
        $label = 'Male';
        $this->genderProvider->expects($this->once())
            ->method('getLabelByName')
            ->with(Gender::MALE)
            ->willReturn($label);

        $this->assertNull(
            self::callTwigFunction($this->extension, 'oro_gender', [null])
        );
        $this->assertEquals(
            $label,
            self::callTwigFunction($this->extension, 'oro_gender', [Gender::MALE])
        );
    }
}
