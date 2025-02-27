<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\Environment\Processor;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Emulates deleting of entity for testing API resource without identifier.
 */
class DeleteTestResourceWithoutIdentifierEntity implements ProcessorInterface
{
    #[\Override]
    public function process(ContextInterface $context): void
    {
        $context->removeResult();
    }
}
