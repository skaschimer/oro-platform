<?php

namespace Oro\Bundle\OrganizationBundle\Provider;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\ScopeBundle\Manager\ScopeCriteriaProviderInterface;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationAwareTokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * The scope criteria provider for the current organization.
 */
class ScopeOrganizationCriteriaProvider implements ScopeCriteriaProviderInterface
{
    public const ORGANIZATION = 'organization';

    /** @var TokenStorageInterface */
    private $tokenStorage;

    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    #[\Override]
    public function getCriteriaField()
    {
        return self::ORGANIZATION;
    }

    #[\Override]
    public function getCriteriaValue()
    {
        $token = $this->tokenStorage->getToken();
        if ($token instanceof OrganizationAwareTokenInterface) {
            return $token->getOrganization();
        }

        return null;
    }

    #[\Override]
    public function getCriteriaValueType()
    {
        return Organization::class;
    }
}
