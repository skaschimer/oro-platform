<?php

namespace Oro\Bundle\UserBundle\Placeholder;

use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserManager;

/**
 * User entity placeholder filter.
 */
class PlaceholderFilter
{
    /** @var TokenAccessorInterface */
    protected $tokenAccessor;

    public function __construct(TokenAccessorInterface $tokenAccessor)
    {
        $this->tokenAccessor = $tokenAccessor;
    }

    /**
     * Checks if password management is available
     *
     * @param object $entity
     * @return bool
     */
    public function isPasswordManageEnabled($entity)
    {
        if ($entity instanceof User &&
            $entity->getAuthStatus() &&
            $entity->getAuthStatus()->getInternalId() !== UserManager::STATUS_ACTIVE
        ) {
            return false;
        }

        return $entity instanceof User && $entity->isEnabled();
    }

    /**
     * Checks if password can be reset
     *
     * @param object $entity
     *
     * @return bool
     */
    public function isPasswordResetEnabled($entity)
    {
        return $entity instanceof User
            && $entity->isEnabled()
            && $this->tokenAccessor->getUserId() !== $entity->getId();
    }

    /**
     * @return bool
     */
    public function isUserApplicable()
    {
        return $this->tokenAccessor->getUser() instanceof User;
    }
}
