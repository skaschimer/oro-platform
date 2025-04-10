<?php

namespace Oro\Bundle\ReportBundle\Form\Type;

use Oro\Bundle\QueryDesignerBundle\Form\Type\AbstractQueryDesignerType;
use Oro\Bundle\ReportBundle\Form\EventListener\DateGroupingFormSubscriber;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Report form type
 */
class ReportType extends AbstractQueryDesignerType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class, array('required' => true))
            ->add('entity', ReportEntityChoiceType::class, array('required' => true))
            ->add(
                'type',
                EntityType::class,
                array(
                    'class'       => \Oro\Bundle\ReportBundle\Entity\ReportType::class,
                    'choice_label'    => 'label',
                    'required'    => true,
                    'placeholder' => 'oro.report.form.choose_report_type'
                )
            )
            ->add(
                'hasChart',
                CheckboxType::class,
                array(
                    'mapped'   => false,
                    'required' => false,
                )
            )
            ->add(
                'chartOptions',
                ReportChartType::class,
                array('required' => true)
            )
            ->add('description', TextareaType::class, array('required' => false));

        parent::buildForm($builder, $options);
        $builder->addEventSubscriber(new DateGroupingFormSubscriber());
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $options = array_merge(
            $this->getDefaultOptions(),
            array(
                'data_class'         => 'Oro\Bundle\ReportBundle\Entity\Report',
                'csrf_token_id'      => 'report',
                'query_type'         => 'report',
            )
        );

        $resolver->setDefaults($options);
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_report';
    }
}
