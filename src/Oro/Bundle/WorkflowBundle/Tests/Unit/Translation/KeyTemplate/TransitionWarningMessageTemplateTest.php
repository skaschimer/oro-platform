<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Translation\KeyTemplate;

use Oro\Bundle\WorkflowBundle\Translation\KeyTemplate\TransitionWarningMessageTemplate;

class TransitionWarningMessageTemplateTest extends TemplateTestCase
{
    #[\Override]
    public function getTemplateInstance()
    {
        return new TransitionWarningMessageTemplate();
    }

    public function testGetName()
    {
        $this->assertName(TransitionWarningMessageTemplate::NAME);
    }

    #[\Override]
    public function testGetTemplate()
    {
        $this->assertTemplate('oro.workflow.{{ workflow_name }}.transition.{{ transition_name }}.warning_message');
    }

    #[\Override]
    public function testGetRequiredKeys()
    {
        $this->assertRequiredKeys(['workflow_name', 'transition_name']);
    }

    public function testGetKeyTemplates()
    {
        $this->assertKeyTemplates([
            'workflow_name' => '{{ workflow_name }}',
            'transition_name' => '{{ transition_name }}',
        ]);
    }
}
