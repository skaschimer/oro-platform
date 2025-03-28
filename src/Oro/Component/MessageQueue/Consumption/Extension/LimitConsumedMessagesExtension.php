<?php

namespace Oro\Component\MessageQueue\Consumption\Extension;

use Oro\Component\MessageQueue\Consumption\AbstractExtension;
use Oro\Component\MessageQueue\Consumption\Context;

/**
 * Interrupts consumption if the message limit reached
 */
class LimitConsumedMessagesExtension extends AbstractExtension
{
    /**
     * @var int
     */
    protected $messageLimit;

    /**
     * @var int
     */
    protected $messageConsumed;

    /**
     * @param int $messageLimit
     */
    public function __construct($messageLimit)
    {
        if (false == is_int($messageLimit)) {
            throw new \InvalidArgumentException(sprintf(
                'Expected message limit is int but got: "%s"',
                is_object($messageLimit) ? get_class($messageLimit) : gettype($messageLimit)
            ));
        }

        $this->messageLimit = $messageLimit;
        $this->messageConsumed = 0;
    }

    #[\Override]
    public function onBeforeReceive(Context $context)
    {
        // this is added here to handle an edge case. when a user sets zero as limit.
        $this->checkMessageLimit($context);
    }

    #[\Override]
    public function onPostReceived(Context $context)
    {
        $this->messageConsumed++;

        $this->checkMessageLimit($context);
    }

    protected function checkMessageLimit(Context $context)
    {
        if ($this->messageConsumed >= $this->messageLimit) {
            $context->getLogger()->debug(sprintf(
                'Message consumption is interrupted since the message limit reached. limit: "%s"',
                $this->messageLimit
            ));

            $context->setExecutionInterrupted(true);
            $context->setInterruptedReason('The message limit reached.');
        }
    }
}
