<?php

namespace Oro\Bundle\AddressBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * This constraint can be used to check if an address region is set if a country has regions.
 */
class RequiredRegion extends Constraint
{
    public $message = 'State is required for country {{ country }}';

    #[\Override]
    public function getTargets(): string|array
    {
        return self::CLASS_CONSTRAINT;
    }
}
