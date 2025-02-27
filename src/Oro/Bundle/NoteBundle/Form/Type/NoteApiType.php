<?php

namespace Oro\Bundle\NoteBundle\Form\Type;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\NoteBundle\Entity\Note;
use Oro\Bundle\NoteBundle\Form\EventListener\NoteSubscriber;
use Oro\Bundle\SoapBundle\Form\EventListener\PatchSubscriber;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * The form type for Note entity REST API.
 */
class NoteApiType extends NoteType
{
    /** @var  ConfigManager $configManager */
    protected $configManager;

    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'message',
                TextType::class,
                [
                    'required' => true,
                    'label' => 'oro.note.message.label'
                ]
            );

        $builder->addEventSubscriber(new PatchSubscriber());
        $builder->addEventSubscriber(new NoteSubscriber($this->configManager));
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class'         => Note::class,
                'csrf_token_id'      => 'note',
                'csrf_protection'    => false
            ]
        );
    }

    #[\Override]
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_note_api';
    }
}
