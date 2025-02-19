<?php

namespace Oro\Bundle\WorkflowBundle\Handler;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\WorkflowBundle\Async\TransitionTriggerMessage;
use Oro\Bundle\WorkflowBundle\Configuration\FeatureConfigurationExtension;
use Oro\Bundle\WorkflowBundle\Entity\BaseTransitionTrigger;
use Oro\Bundle\WorkflowBundle\Entity\TransitionCronTrigger;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Helper\TransitionCronTriggerHelper;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Bundle\WorkflowBundle\Model\WorkflowStartArguments;

/**
 * This handler performs the actual processing of transition trigger messages produced by transition cron triggers
 */
class TransitionCronTriggerHandler implements TransitionTriggerHandlerInterface
{
    /** @var WorkflowManager */
    private $workflowManager;

    /** @var TransitionCronTriggerHelper */
    private $helper;

    /** * @var FeatureChecker */
    private $featureChecker;

    public function __construct(
        WorkflowManager $workflowManager,
        TransitionCronTriggerHelper $helper,
        FeatureChecker $featureChecker
    ) {
        $this->workflowManager = $workflowManager;
        $this->helper = $helper;
        $this->featureChecker = $featureChecker;
    }

    /**
     * @throws \Exception
     * @throws \InvalidArgumentException
     * @throws \Oro\Bundle\WorkflowBundle\Exception\WorkflowException
     */
    #[\Override]
    public function process(BaseTransitionTrigger $trigger, TransitionTriggerMessage $message)
    {
        if (!$trigger instanceof TransitionCronTrigger) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Cron trigger should be an instance of %s, %s instance given',
                    TransitionCronTrigger::class,
                    ClassUtils::getClass($trigger)
                )
            );
        }

        $isWorkflowEnabledInFeatures = $this->featureChecker->isResourceEnabled(
            $trigger->getWorkflowDefinition()->getName(),
            FeatureConfigurationExtension::WORKFLOWS_NODE_NAME
        );
        if (!$isWorkflowEnabledInFeatures) {
            return false;
        }

        $workflow = $this->workflowManager->getWorkflow($trigger->getWorkflowDefinition()->getName());
        if (!$workflow) {
            return false;
        }

        $transition = $workflow->getTransitionManager()->getTransition($trigger->getTransitionName());
        if (!$transition) {
            return false;
        }

        if ($transition->isStart()) {
            $this->processStartTransition($trigger, $workflow);
        } else {
            $this->processTransition($trigger, $workflow);
        }

        return true;
    }

    protected function processStartTransition(TransitionCronTrigger $trigger, Workflow $workflow)
    {
        $entities = $this->helper->fetchEntitiesWithoutWorkflowItems($trigger, $workflow);

        $workflowStartArguments = array_map(
            function ($entity) use ($workflow, $trigger) {
                return new WorkflowStartArguments($workflow->getName(), $entity, [], $trigger->getTransitionName());
            },
            $entities
        );

        $this->workflowManager->massStartWorkflow($workflowStartArguments);
    }

    /**
     * @throws \Exception
     */
    protected function processTransition(TransitionCronTrigger $trigger, Workflow $workflow)
    {
        $workflowItems = $this->helper->fetchWorkflowItemsForTrigger($trigger, $workflow);

        $data = array_map(
            function (WorkflowItem $workflowItem) use ($trigger) {
                return [
                    'workflowItem' => $workflowItem,
                    'transition' => $trigger->getTransitionName(),
                ];
            },
            $workflowItems
        );

        $this->workflowManager->massTransit($data);
    }
}
