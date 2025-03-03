<?php

namespace Oro\Bundle\WorkflowBundle\Serializer;

use Oro\Bundle\ImportExportBundle\Serializer\Serializer;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowRegistry;

class WorkflowDataSerializer extends Serializer implements WorkflowAwareSerializer
{
    /**
     * @var string
     */
    protected $workflowName;

    /**
     * @var WorkflowRegistry
     */
    protected $workflowRegistry;

    public function setWorkflowRegistry(WorkflowRegistry $workflowRegistry)
    {
        $this->workflowRegistry = $workflowRegistry;
    }

    /**
     * @return Workflow
     */
    #[\Override]
    public function getWorkflow()
    {
        return $this->workflowRegistry->getWorkflow($this->getWorkflowName());
    }

    /**
     * @param string $workflowName
     */
    #[\Override]
    public function setWorkflowName($workflowName)
    {
        $this->workflowName = $workflowName;
    }

    /**
     * @return string
     */
    #[\Override]
    public function getWorkflowName()
    {
        return $this->workflowName;
    }
}
