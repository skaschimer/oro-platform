<?php

namespace Oro\Bundle\EntityMergeBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * This constraint is used to check that the list of entities does not contain duplicates.
 */
class UniqueEntity extends Constraint
{
    public string $message = 'Merge entities should be unique.';

    #[\Override]
    public function getTargets(): string|array
    {
        return self::CLASS_CONSTRAINT;
    }
}
