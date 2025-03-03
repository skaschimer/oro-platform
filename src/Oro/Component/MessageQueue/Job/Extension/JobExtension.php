<?php

namespace Oro\Component\MessageQueue\Job\Extension;

use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Log\ConsumerState;

/**
 * Updates the consumer state with the current job.
 */
class JobExtension extends AbstractExtension
{
    /** @var ConsumerState */
    private $consumerState;

    public function __construct(ConsumerState $consumerState)
    {
        $this->consumerState = $consumerState;
    }

    #[\Override]
    public function onPreRunUnique(Job $job)
    {
        $this->consumerState->setJob($job);
    }

    #[\Override]
    public function onPostRunUnique(Job $job, $jobResult)
    {
        $this->consumerState->setJob();
    }

    #[\Override]
    public function onPreRunDelayed(Job $job)
    {
        $this->consumerState->setJob($job);
    }

    #[\Override]
    public function onPostRunDelayed(Job $job, $jobResult)
    {
        $this->consumerState->setJob();
    }

    #[\Override]
    public function onCancel(Job $job)
    {
        $this->consumerState->setJob();
    }
}
