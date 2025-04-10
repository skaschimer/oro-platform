<?php

namespace Oro\Bundle\FormBundle\Form\Extension;

use Oro\Bundle\FormBundle\Form\Extension\Traits\FormExtendedTypeTrait;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DataBlockExtension extends AbstractTypeExtension
{
    use FormExtendedTypeTrait;

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefined(
            array(
                'block',
                'subblock',
                'block_config',
                'tooltip'
            )
        );
    }

    #[\Override]
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        if (isset($options['block'])) {
            $view->vars['block'] = $options['block'];
        }

        if (isset($options['subblock'])) {
            $view->vars['subblock'] = $options['subblock'];
        }

        if (isset($options['block_config'])) {
            $view->vars['block_config'] = $options['block_config'];
        }

        if (isset($options['tooltip'])) {
            $view->vars['tooltip'] = $options['tooltip'];
        }
    }
}
