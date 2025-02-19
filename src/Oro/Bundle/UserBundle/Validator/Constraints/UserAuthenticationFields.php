<?php

namespace Oro\Bundle\UserBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * This constraint is used to check that username is not equal to Primary Email for another user.
 */
class UserAuthenticationFields extends Constraint
{
    public string $message = 'oro.user.message.invalid_username';

    #[\Override]
    public function getTargets(): string|array
    {
        return self::CLASS_CONSTRAINT;
    }
}
