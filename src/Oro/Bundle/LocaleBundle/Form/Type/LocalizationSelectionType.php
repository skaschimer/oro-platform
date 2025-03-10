<?php

namespace Oro\Bundle\LocaleBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\OroChoiceType;
use Oro\Bundle\LocaleBundle\DependencyInjection\Configuration;
use Oro\Bundle\LocaleBundle\Manager\LocalizationManager;
use Oro\Bundle\LocaleBundle\Provider\LocalizationChoicesProvider;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Provides a list of Localizations to choose the necessary localizations in system configuration.
 */
class LocalizationSelectionType extends AbstractType
{
    private const NAME = 'oro_locale_localization_selection';

    /** @var LocalizationManager */
    private $localizationManager;

    /** @var LocalizationChoicesProvider */
    private $localizationChoicesProvider;

    public function __construct(
        LocalizationManager $localizationManager,
        LocalizationChoicesProvider $localizationChoicesProvider
    ) {
        $this->localizationManager = $localizationManager;
        $this->localizationChoicesProvider = $localizationChoicesProvider;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'choices' => $this->getLocalizationChoices(null),
            'placeholder' => '',
            'translatable_options' => false,
            'configs' => [
                'placeholder' => 'oro.locale.localization.form.placeholder.select_localization',
            ],
            Configuration::ENABLED_LOCALIZATIONS => null
        ]);
        $resolver->setAllowedTypes(Configuration::ENABLED_LOCALIZATIONS, ['null', 'array']);
    }

    #[\Override]
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $localizationChoices = $this->getLocalizationChoices($options[Configuration::ENABLED_LOCALIZATIONS]);

        $view->vars['choices'] = array_filter(
            $view->vars['choices'],
            function (ChoiceView $choiceView) use ($localizationChoices) {
                return in_array($choiceView->data, $localizationChoices, true);
            }
        );
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return static::NAME;
    }

    #[\Override]
    public function getParent(): ?string
    {
        return OroChoiceType::class;
    }

    private function getLocalizationChoices(?array $enabledLocalization): array
    {
        $availableLocalizations = $this->localizationChoicesProvider->getLocalizationChoices();

        if ($enabledLocalization !== null) {
            $enabledLocalization = $this->localizationManager->getLocalizations($enabledLocalization);

            $availableLocalizations = array_flip(
                array_intersect_key(
                    array_flip($availableLocalizations),
                    $enabledLocalization
                )
            );
        }

        return $availableLocalizations;
    }
}
