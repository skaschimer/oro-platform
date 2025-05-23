<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Form\Type;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\FormBundle\Autocomplete\SearchHandlerInterface;
use Oro\Bundle\FormBundle\Autocomplete\SearchRegistry;
use Oro\Bundle\FormBundle\Form\DataTransformer\EntitiesToIdsTransformer;
use Oro\Bundle\FormBundle\Form\Type\OroJquerySelect2HiddenType;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Form\Type\RoleMultiSelectType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RoleMultiSelectTypeTest extends FormIntegrationTestCase
{
    private ManagerRegistry&MockObject $doctrine;
    private EntityManagerInterface&MockObject $em;
    private RoleMultiSelectType $formType;

    #[\Override]
    protected function setUp(): void
    {
        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->expects($this->any())
            ->method('getSingleIdentifierFieldName')
            ->willReturn('id');

        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->em->expects($this->any())
            ->method('getClassMetadata')
            ->with(Role::class)
            ->willReturn($metadata);

        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($this->em);

        $this->formType = new RoleMultiSelectType($this->doctrine);

        parent::setUp();
    }

    public function testGetParent()
    {
        $this->assertEquals(OroJquerySelect2HiddenType::class, $this->formType->getParent());
    }

    public function testConfigureOptions()
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(
                [
                    'autocomplete_alias' => 'roles',
                    'configs' => [
                        'multiple' => true,
                        'placeholder' => 'oro.user.form.choose_role',
                        'allowClear' => true,
                    ]
                ]
            );

        $this->formType->configureOptions($resolver);
    }

    public function testBuildView()
    {
        $builder = $this->createMock(FormBuilder::class);
        $builder->expects($this->once())
            ->method('addModelTransformer')
            ->with(new EntitiesToIdsTransformer($this->doctrine, Role::class));

        $this->formType->buildForm($builder, ['entity_class' => Role::class]);
    }

    /**
     * @dataProvider submitDataProvider
     */
    public function testSubmitEmptyData(string|array $submittedData, array $expected)
    {
        $form = $this->factory->create(RoleMultiSelectType::class, null, ['entity_class' => Role::class]);
        $form->submit($submittedData);

        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($expected, $form->getData());
    }

    public function submitDataProvider(): array
    {
        return [
            'empty string' => [
                'submittedData' => '',
                'expected' => [],
            ],
        ];
    }

    #[\Override]
    protected function getExtensions(): array
    {
        $searchHandler = $this->createMock(SearchHandlerInterface::class);
        $searchHandler->expects($this->any())
            ->method('getProperties')
            ->willReturn(['label']);

        $searchRegistry = $this->createMock(SearchRegistry::class);
        $searchRegistry->expects($this->any())
            ->method('getSearchHandler')
            ->with('roles')
            ->willReturn($searchHandler);

        return [
            new PreloadedExtension(
                [
                    $this->formType,
                    new OroJquerySelect2HiddenType(
                        $this->doctrine,
                        $searchRegistry,
                        $this->createMock(ConfigProvider::class)
                    )
                ],
                []
            ),
        ];
    }
}
