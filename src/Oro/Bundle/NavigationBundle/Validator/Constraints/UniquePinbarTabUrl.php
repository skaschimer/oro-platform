<?php

namespace Oro\Bundle\NavigationBundle\Validator\Constraints;

use Oro\Bundle\NavigationBundle\Entity\PinbarTab;
use Symfony\Component\Validator\Constraint;

/**
 * PinbarTab URL must be unique for each user.
 */
class UniquePinbarTabUrl extends Constraint
{
    /** @var string  */
    public $message = 'oro.navigation.validator.unique_pinbar_tab_url.error';

    /** @var string */
    public $pinbarTabClass = PinbarTab::class;

    #[\Override]
    public function getTargets(): string|array
    {
        return self::CLASS_CONSTRAINT;
    }

    #[\Override]
    public function validatedBy(): string
    {
        return UniquePinbarTabUrlValidator::class;
    }
}
