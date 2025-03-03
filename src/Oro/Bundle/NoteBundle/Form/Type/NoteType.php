<?php

namespace Oro\Bundle\NoteBundle\Form\Type;

use Oro\Bundle\AttachmentBundle\Form\Type\ImageType;
use Oro\Bundle\FormBundle\Form\Type\OroResizeableRichTextType;
use Oro\Bundle\NoteBundle\Entity\Note;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * The form type for Note entity.
 */
class NoteType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'message',
                OroResizeableRichTextType::class,
                [
                    'required' => true,
                    'label'    => 'oro.note.message.label'
                ]
            )
            ->add(
                'attachment',
                ImageType::class,
                [
                    'label' => 'oro.note.attachment.label',
                    'required' => false
                ]
            );
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class'              => Note::class,
                'csrf_token_id'           => 'note',
                'ownership_disabled'      => true,
                'dynamic_fields_disabled' => true,
                'csrf_protection'         => true,
                'contexts_options'        => [
                    'constraints' => [
                        new NotBlank()
                    ],
                    'required' => true
                ]
            ]
        );
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_note';
    }
}
