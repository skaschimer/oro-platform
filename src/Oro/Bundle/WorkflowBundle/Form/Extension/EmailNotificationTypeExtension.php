<?php

namespace Oro\Bundle\WorkflowBundle\Form\Extension;

use Oro\Bundle\NotificationBundle\Form\Type\EmailNotificationType;
use Oro\Bundle\WorkflowBundle\Form\EventListener\EmailNotificationTypeListener;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

/**
 * Update email notification form when event name or workflow name is changed
 */
class EmailNotificationTypeExtension extends AbstractTypeExtension
{
    /** @var EmailNotificationTypeListener */
    protected $listener;

    public function __construct(EmailNotificationTypeListener $listener)
    {
        $this->listener = $listener;
    }

    #[\Override]
    public static function getExtendedTypes(): iterable
    {
        return [EmailNotificationType::class];
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(FormEvents::POST_SET_DATA, [$this->listener, 'onPostSetData']);
        $builder->addEventListener(FormEvents::PRE_SUBMIT, [$this->listener, 'onPreSubmit']);
    }

    #[\Override]
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $elements = array_filter(
            array_map(
                function (FormView $view) {
                    if (in_array($view->vars['name'], ['eventName', 'workflow_definition'], true)) {
                        return '#' . $view->vars['id'];
                    }

                    return null;
                },
                array_values($view->children)
            )
        );

        $view->vars['listenChangeElements'] = array_merge($view->vars['listenChangeElements'], $elements);
    }
}
