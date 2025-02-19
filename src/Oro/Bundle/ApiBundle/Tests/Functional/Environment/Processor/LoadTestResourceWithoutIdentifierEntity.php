<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\Environment\Processor;

use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Model\TestResourceWithoutIdentifier;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Emulates loading of entity for testing API resource without identifier.
 */
class LoadTestResourceWithoutIdentifierEntity implements ProcessorInterface
{
    #[\Override]
    public function process(ContextInterface $context): void
    {
        $context->setResult(new TestResourceWithoutIdentifier());
    }
}
