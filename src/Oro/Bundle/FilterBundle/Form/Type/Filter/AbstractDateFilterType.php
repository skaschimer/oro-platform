<?php

namespace Oro\Bundle\FilterBundle\Form\Type\Filter;

use Oro\Bundle\FilterBundle\Form\EventListener\DateFilterSubmitContext;
use Oro\Bundle\FilterBundle\Form\EventListener\DateFilterSubscriber;
use Oro\Bundle\FilterBundle\Provider\DateModifierInterface;
use Oro\Bundle\FilterBundle\Provider\DateModifierProvider;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Abstract form type for date filter forms.
 */
abstract class AbstractDateFilterType extends AbstractType
{
    const TYPE_NONE        = 0;
    const TYPE_BETWEEN     = 1;
    const TYPE_NOT_BETWEEN = 2;
    const TYPE_MORE_THAN   = 3;
    const TYPE_LESS_THAN   = 4;
    const TYPE_EQUAL       = 5;
    const TYPE_NOT_EQUAL   = 6;

    const TYPE_TODAY        = 7;
    const TYPE_THIS_WEEK    = 8;
    const TYPE_THIS_MONTH   = 9;
    const TYPE_THIS_QUARTER = 10;
    const TYPE_THIS_YEAR    = 11;
    const TYPE_ALL_TIME     = 12;

    /**
     * @var array
     */
    public static $valueTypes = [
        self::TYPE_TODAY,
        self::TYPE_THIS_WEEK,
        self::TYPE_THIS_MONTH,
        self::TYPE_THIS_QUARTER,
        self::TYPE_THIS_YEAR,
        self::TYPE_ALL_TIME,
    ];

    /** @var TranslatorInterface */
    protected $translator;

    /** @var DateModifierProvider */
    protected $dateModifiers;

    /** @var null|array */
    protected $dateVarsChoices;

    /** @var null|array */
    protected $datePartsChoices;

    /** @var DateFilterSubscriber */
    protected $subscriber;

    public function __construct(
        TranslatorInterface $translator,
        DateModifierInterface $dateModifiers,
        EventSubscriberInterface $subscriber
    ) {
        $this->translator    = $translator;
        $this->dateModifiers = $dateModifiers;
        $this->subscriber    = $subscriber;
    }

    /**
     * @return array
     */
    public static function getTypeValues()
    {
        return [
            'between'    => self::TYPE_BETWEEN,
            'notBetween' => self::TYPE_NOT_BETWEEN,
            'moreThan'   => self::TYPE_MORE_THAN,
            'lessThan'   => self::TYPE_LESS_THAN,
            'equal'      => self::TYPE_EQUAL,
            'notEqual'   => self::TYPE_NOT_EQUAL
        ];
    }

    /**
     * @return array
     */
    public function getOperatorChoices()
    {
        return [
            $this->translator->trans('oro.filter.form.label_date_type_between') => self::TYPE_BETWEEN,
            $this->translator->trans('oro.filter.form.label_date_type_not_between') => self::TYPE_NOT_BETWEEN,
            $this->translator->trans('oro.filter.form.label_date_type_more_than') => self::TYPE_MORE_THAN,
            $this->translator->trans('oro.filter.form.label_date_type_less_than') => self::TYPE_LESS_THAN,
            $this->translator->trans('oro.filter.form.label_date_type_equals') => self::TYPE_EQUAL,
            $this->translator->trans('oro.filter.form.label_date_type_not_equals') => self::TYPE_NOT_EQUAL
        ];
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'date_parts'     => $this->getDateParts(),
            'date_vars'      => $this->getDateVariables(),
            'compile_date'   => true,
            'submit_context' => null
        ]);
        $resolver->setNormalizer('submit_context', function () {
            return new DateFilterSubmitContext();
        });
    }

    /**
     * @return array|null
     */
    protected function getDateParts()
    {
        if (is_null($this->datePartsChoices)) {
            $t                      = $this->translator;
            $this->datePartsChoices = array_map(
                function ($item) use ($t) {
                    return $t->trans($item);
                },
                $this->dateModifiers->getDateParts()
            );
        }

        return $this->datePartsChoices;
    }

    /**
     * @return array|null
     */
    protected function getDateVariables()
    {
        if (is_null($this->dateVarsChoices)) {
            $t      = $this->translator;
            $result = $this->dateModifiers->getDateVariables();

            foreach ($result as $part => $vars) {
                $result[$part] = array_map(
                    function ($item) use ($t) {
                        return $t->trans($item);
                    },
                    $vars
                );
            }
            $this->dateVarsChoices = $result;
        }

        return $this->dateVarsChoices;
    }

    #[\Override]
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['widget_options'] = $options['widget_options'];
        $view->vars['date_parts']     = $options['date_parts'];
        $view->vars['date_vars']      = $options['date_vars'];
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if (!isset($options['date_parts'])) {
            $options['date_parts'] = [];
        }

        $builder->add(
            'part',
            ChoiceType::class,
            [
                'choices' => array_flip($options['date_parts']),
            ]
        );

        if ($options['compile_date']) {
            $builder->addEventSubscriber($this->subscriber);
        }
    }
}
