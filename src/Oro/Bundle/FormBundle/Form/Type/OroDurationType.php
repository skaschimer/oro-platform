<?php

namespace Oro\Bundle\FormBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\DataTransformer\DurationToStringTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Duration field type
 * Accepts numeric values (seconds), JIRA style (##h ##m ##s) and column style (##:##:##) duration encodings.
 *
 * @see DurationToStringTransformer for more details
 */
class OroDurationType extends AbstractType
{
    const NAME = 'oro_duration';

    const VALIDATION_REGEX_JIRA   = '/^
                                    (?:(?:(\d+(?:[\.,]\d{0,2})?)?)h
                                    (?:[\s]*|$))?(?:(?:(\d+(?:[\.,]\d{0,2})?)?)m
                                    (?:[\s]*|$))?(?:(?:(\d+(?:[\.,]\d{0,2})?)?)s?)?
                                    $/ix';

    const VALIDATION_REGEX_COLUMN = '/^
                                    ((\d{1,3}:)?\d{1,3}:)?\d{1,3}
                                    $/ix';

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addModelTransformer(new DurationToStringTransformer());
        // We need to validate user input before it is passed to the model transformer
        $builder->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'preSubmit']);
    }

    /**
     * Event listener callback to handle validation before data is submitted.
     */
    public function preSubmit(FormEvent $event)
    {
        if ($this->isValidDuration($event->getData())) {
            return;
        }
        $event->getForm()->addError(new FormError('Value is not in a valid duration format'));
    }

    /**
     * Checks whether string is in a recognizable duration format
     *
     * @param string $value
     * @return bool
     */
    protected function isValidDuration($value)
    {
        return preg_match(self::VALIDATION_REGEX_JIRA, $value) ||
               preg_match(self::VALIDATION_REGEX_COLUMN, $value);
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'tooltip' => 'oro.form.oro_duration.tooltip',
                'validation_groups' => false, // disable frontend validators, we validate before submit
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
        return self::NAME;
    }

    #[\Override]
    public function getParent(): ?string
    {
        return TextType::class;
    }
}
