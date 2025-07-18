<?php

namespace Oro\Bundle\AddressBundle\Tests\Unit\Extension\JsValidation;

use Oro\Bundle\AddressBundle\Extension\JsValidation\ConstraintsProviderDecorator;
use Oro\Bundle\AddressBundle\Validator\Constraints\NameOrOrganization;
use Oro\Bundle\FormBundle\Form\Extension\JsValidation\ConstraintsProviderInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class ConstraintsProviderDecoratorTest extends TestCase
{
    private ConstraintsProviderInterface&MockObject $provider;
    private ConstraintsProviderDecorator $providerDecorator;

    #[\Override]
    protected function setUp(): void
    {
        $this->provider = $this->createMock(ConstraintsProviderInterface::class);

        $this->providerDecorator = new ConstraintsProviderDecorator($this->provider);
    }

    public function testGetFormConstraintsNoParentForm(): void
    {
        $constraints = [new NameOrOrganization()];

        $this->provider->expects($this->any())
            ->method('getFormConstraints')
            ->willReturn($constraints);

        $form = $this->createMock(FormInterface::class);
        $form->expects($this->any())
            ->method('getParent')
            ->willReturn(null);

        self::assertEquals($constraints, $this->providerDecorator->getFormConstraints($form));
    }

    public function testGetFormConstraintsHasParentNoNameOrOrganization(): void
    {
        $constraints = [new NotBlank()];

        $this->provider->expects($this->any())
            ->method('getFormConstraints')
            ->willReturn($constraints);

        $form = $this->createMock(FormInterface::class);
        $form->expects($this->any())
            ->method('getParent')
            ->willReturn($this->createMock(FormInterface::class));

        self::assertEquals($constraints, $this->providerDecorator->getFormConstraints($form));
    }

    public function testGetFormConstraintsHasParentNoNameOrOrganizationAndNameOrOrganizationConstraint(): void
    {
        $constraints = [new NotBlank(), new NameOrOrganization()];

        $this->provider->expects($this->any())
            ->method('getFormConstraints')
            ->willReturn($constraints);

        $form = $this->createMock(FormInterface::class);
        $form->expects($this->any())
            ->method('getParent')
            ->willReturn($this->createMock(FormInterface::class));
        $form->expects($this->any())
            ->method('getName')
            ->willReturn('parentName');

        $expectedConstraints = [new NotBlank(), new NameOrOrganization(['parentFormName' => 'parentName'])];
        self::assertEquals($expectedConstraints, $this->providerDecorator->getFormConstraints($form));
    }
}
