<?php

declare(strict_types=1);

namespace Oro\Component\Layout\Tests\Unit\Loader\Stubs;

use Oro\Component\Layout\Loader\Generator\VisitContext;
use Oro\Component\Layout\Loader\Visitor\VisitorInterface;

class StubConditionVisitor implements VisitorInterface
{
    #[\Override]
    public function startVisit(VisitContext $visitContext): void
    {
        $visitContext->appendToUpdateMethodBody('if (true) {');
    }

    #[\Override]
    public function endVisit(VisitContext $visitContext): void
    {
        $visitContext->appendToUpdateMethodBody('}');
    }
}
