<?php

namespace Oro\Bundle\WorkflowBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\OroChoiceType;
use Oro\Bundle\WorkflowBundle\Helper\WorkflowTranslationHelper;
use Oro\Bundle\WorkflowBundle\Model\WorkflowRegistry;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Covers logic of selecting Workflow Transitions.
 */
class WorkflowTransitionSelectType extends AbstractType
{
    const NAME = 'oro_workflow_transition_select';

    /** @var WorkflowRegistry $workflowRegistry */
    protected $workflowRegistry;

    /** @var TranslatorInterface */
    protected $translator;

    public function __construct(WorkflowRegistry $workflowRegistry, TranslatorInterface $translator)
    {
        $this->workflowRegistry = $workflowRegistry;
        $this->translator = $translator;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefined('workflowName');
        $resolver->setAllowedTypes('workflowName', ['string', 'null']);
        $resolver->setRequired('workflowName');

        $resolver->setNormalizer(
            'choices',
            function (Options $options, $choices) {
                if (!empty($choices) || !$options['workflowName']) {
                    return $choices;
                }

                $workflow = $this->workflowRegistry->getWorkflow($options['workflowName'], false);
                $transitions = $workflow->getTransitionManager()->getTransitions();

                $choices = [];
                foreach ($transitions as $transition) {
                    $choices[$transition->getLabel()] = $transition->getName();
                }

                return $choices;
            }
        );
    }
    #[\Override]
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        /** @var ChoiceView $choiceView */
        foreach ($view->vars['choices'] as $choiceView) {
            $translatedLabel = $this->translator->trans(
                (string) $choiceView->label,
                [],
                WorkflowTranslationHelper::TRANSLATION_DOMAIN
            );
            $choiceView->label = sprintf('%s (%s)', $translatedLabel, $choiceView->value);
        }
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
        return OroChoiceType::class;
    }
}
