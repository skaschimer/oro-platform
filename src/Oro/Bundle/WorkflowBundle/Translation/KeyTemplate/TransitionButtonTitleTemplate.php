<?php

namespace Oro\Bundle\WorkflowBundle\Translation\KeyTemplate;

/**
 * Transition button title key template.
 */
class TransitionButtonTitleTemplate extends TransitionTemplate
{
    const NAME = 'transition_button_title';

    #[\Override]
    public function getTemplate(): string
    {
        return parent::getTemplate() . '.button_title';
    }
}
