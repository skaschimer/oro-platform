<?php

namespace Oro\Component\MessageQueue\Consumption\Dbal\Extension;

use Oro\Component\MessageQueue\Consumption\AbstractExtension;
use Oro\Component\MessageQueue\Consumption\Context;

/**
 * Rejects message on DBAL exception
 */
class RejectMessageOnExceptionDbalExtension extends AbstractExtension
{
    #[\Override]
    public function onInterrupted(Context $context)
    {
        if (! $context->getException()) {
            return;
        }

        if (! $context->getMessage()) {
            return;
        }

        $context->getMessageConsumer()->reject($context->getMessage(), true);

        $context->getLogger()->debug(
            'Execution was interrupted and message was rejected. {id}',
            ['id' => $context->getMessage()->getMessageId()]
        );
    }
}
