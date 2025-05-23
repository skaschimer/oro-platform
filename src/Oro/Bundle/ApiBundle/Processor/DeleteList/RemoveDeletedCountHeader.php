<?php

namespace Oro\Bundle\ApiBundle\Processor\DeleteList;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Removes the "X-Include-Deleted-Count" response header if any error occurs.
 */
class RemoveDeletedCountHeader implements ProcessorInterface
{
    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var DeleteListContext $context */

        if ($context->hasErrors()
            && $context->getResponseHeaders()->has(SetDeletedCountHeader::RESPONSE_HEADER_NAME)
        ) {
            $context->getResponseHeaders()->remove(SetDeletedCountHeader::RESPONSE_HEADER_NAME);
        }
    }
}
