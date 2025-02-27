<?php

namespace Oro\Bundle\FormBundle\Form\Type;

use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\LessThan;

class OroMoneyType extends AbstractType
{
    const NAME = 'oro_money';

    /**
     * @var LocaleSettings
     */
    protected $localeSettings;

    /**
     * @var NumberFormatter
     */
    protected $numberFormatter;

    public function __construct(LocaleSettings $localeSettings, NumberFormatter $numberFormatter)
    {
        $this->localeSettings = $localeSettings;
        $this->numberFormatter = $numberFormatter;
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
        return MoneyType::class;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $currencyCode = $this->localeSettings->getCurrency();
        $currencySymbol = $this->localeSettings->getCurrencySymbolByCurrency($currencyCode);

        $resolver->setDefaults(
            array(
                'currency'        => $currencyCode,
                'currency_symbol' => $currencySymbol,
                'grouping'        => (bool)$this->numberFormatter->getAttribute(\NumberFormatter::GROUPING_USED),
                'constraints'     => array(
                    new LessThan(array('value' => pow(10, 15)))
                )
            )
        );
    }

    #[\Override]
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $currency = $options['currency'];
        $isPrepend = $this->numberFormatter->isCurrencySymbolPrepend($currency);

        $view->vars['money_pattern'] = $this->getPattern($currency, $isPrepend);
        $view->vars['currency_symbol'] = $options['currency_symbol'];
        $view->vars['currency_symbol_prepend'] = $isPrepend;
    }

    /**
     * @param string $currency
     * @param bool|null $isPrepend
     * @return string
     */
    protected function getPattern($currency, $isPrepend)
    {
        $pattern = '{{ widget }}';
        if (!$currency || null === $isPrepend) {
            return $pattern;
        }

        if ($isPrepend) {
            $pattern = '{{ currency }}' . $pattern;
        } else {
            $pattern = $pattern . '{{ currency }}';
        }

        return $pattern;
    }
}
