<?php

namespace Oro\Bundle\WorkflowBundle\Translation\KeyTemplate;

/**
 * Workflow attribute key template.
 */
class WorkflowAttributeTemplate extends WorkflowTemplate
{
    const NAME = 'workflow_attribute';

    #[\Override]
    public function getTemplate(): string
    {
        return parent::getTemplate() . '.attribute.{{ attribute_name }}';
    }

    /**
     * @return array
     */
    #[\Override]
    public function getRequiredKeys()
    {
        return array_merge(parent::getRequiredKeys(), ['attribute_name']);
    }
}
