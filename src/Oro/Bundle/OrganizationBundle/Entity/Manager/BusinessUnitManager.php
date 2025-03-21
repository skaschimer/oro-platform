<?php

namespace Oro\Bundle\OrganizationBundle\Entity\Manager;

use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Entity\Repository\BusinessUnitRepository;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\SecurityBundle\Owner\OwnerTreeProviderInterface;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserInterface;

/**
 * Provides a set of methods to manage business units.
 */
class BusinessUnitManager
{
    public function __construct(
        protected ManagerRegistry $doctrine,
        protected TokenAccessorInterface $tokenAccessor,
        protected AclHelper $aclHelper
    ) {
    }

    /**
     * Get Business Units tree
     *
     * @param User|null $entity
     * @param int|null $organizationId
     *
     * @return array
     */
    public function getBusinessUnitsTree(?User $entity = null, $organizationId = null)
    {
        return $this->getBusinessUnitRepo()->getBusinessUnitsTree($entity, $organizationId);
    }

    /**
     * Get business units ids
     *
     * @param int|null $organizationId
     *
     * @return array
     */
    public function getBusinessUnitIds($organizationId = null)
    {
        return $this->getBusinessUnitRepo()->getBusinessUnitIds($organizationId);
    }

    /**
     * Get Current BU ID with child BU IDs
     *
     * @param int $businessUnitId
     * @param int $organizationId
     *
     * @return array
     */
    public function getChildBusinessUnitIds($businessUnitId, $organizationId)
    {
        $tree = $this->getBusinessUnitsTree(null, $organizationId);
        $currentBuTree = $this->getBuWithChildTree($businessUnitId, $tree);

        return array_merge(
            [$currentBuTree['id']],
            isset($currentBuTree['children']) ? $this->getTreeIds($currentBuTree['children']) : []
        );
    }

    /**
     * @param array $criteria
     * @param array|null $orderBy
     *
     * @return BusinessUnit
     */
    public function getBusinessUnit(array $criteria = [], ?array $orderBy = null)
    {
        return $this->getBusinessUnitRepo()->findOneBy($criteria, $orderBy);
    }

    /**
     * Checks if user can be set as owner by given user
     *
     * @param User                       $currentUser
     * @param User                       $newUser
     * @param string                     $accessLevel
     * @param OwnerTreeProviderInterface $treeProvider
     * @param Organization               $organization
     *
     * @return bool
     */
    public function canUserBeSetAsOwner(
        UserInterface $currentUser,
        UserInterface $newUser,
        $accessLevel,
        OwnerTreeProviderInterface $treeProvider,
        Organization $organization
    ) {
        if (AccessLevel::SYSTEM_LEVEL === $accessLevel) {
            return true;
        }

        if (AccessLevel::BASIC_LEVEL === $accessLevel) {
            return $newUser->getId() == $currentUser->getId();
        }

        if (AccessLevel::GLOBAL_LEVEL === $accessLevel) {
            return $newUser->isBelongToOrganization($organization);
        }

        $allowedBuIds = $this->getAllowedBusinessUnitIds($currentUser, $accessLevel, $treeProvider, $organization);
        if (empty($allowedBuIds)) {
            return false;
        }

        $newUserBuIds = $treeProvider->getTree()->getUserBusinessUnitIds(
            $newUser->getId(),
            $organization->getId()
        );
        $intersectIds = array_intersect($allowedBuIds, $newUserBuIds);

        return !empty($intersectIds);
    }

    /**
     * Checks if Business Unit can be set as owner by given user
     *
     * @param User                       $currentUser
     * @param BusinessUnit               $entityOwner
     * @param string                     $accessLevel
     * @param OwnerTreeProviderInterface $treeProvider
     * @param Organization               $organization
     *
     * @return bool
     */
    public function canBusinessUnitBeSetAsOwner(
        User $currentUser,
        BusinessUnit $entityOwner,
        $accessLevel,
        OwnerTreeProviderInterface $treeProvider,
        Organization $organization
    ) {
        if (AccessLevel::SYSTEM_LEVEL === $accessLevel) {
            return true;
        }

        $allowedBuIds = AccessLevel::GLOBAL_LEVEL === $accessLevel
            ? $this->getBusinessUnitIds($this->tokenAccessor->getOrganizationId())
            : $this->getAllowedBusinessUnitIds($currentUser, $accessLevel, $treeProvider, $organization);

        return in_array($entityOwner->getId(), $allowedBuIds, true);
    }

    /**
     * @param User $user
     * @param Organization $organization
     *
     * @return BusinessUnit|null
     */
    public function getCurrentBusinessUnit(User $user, Organization $organization)
    {
        return $this->getBusinessUnitRepo()->getFirstAllowedBusinessUnit($user, $organization);
    }

    /**
     * @return BusinessUnitRepository
     */
    public function getBusinessUnitRepo()
    {
        return $this->doctrine->getRepository(BusinessUnit::class);
    }

    /**
     * @return EntityRepository
     */
    public function getUserRepo()
    {
        return $this->doctrine->getRepository(User::class);
    }

    /**
     * Prepare choice options for a hierarchical select
     *
     * @param array $options
     * @param int   $level
     *
     * @return array
     */
    public function getTreeOptions($options, $level = 0)
    {
        $choices = [];
        $blanks  = str_repeat('&nbsp;&nbsp;&nbsp;', $level);
        foreach ($options as $option) {
            $choices += [$blanks . htmlspecialchars($option['name']) => $option['id']];
            if (isset($option['children'])) {
                $choices += $this->getTreeOptions($option['children'], $level + 1);
            }
        }

        return $choices;
    }

    /**
     * @param array $tree
     *
     * @return int
     */
    public function getTreeNodesCount($tree)
    {
        return array_reduce(
            $tree,
            function ($count, $node) {
                return $count + (isset($node['children']) ? $this->getTreeNodesCount($node['children']) : 0);
            },
            count($tree)
        );
    }

    /**
     * @param array $children
     *
     * @return array
     */
    protected function getTreeIds($children)
    {
        $result = [];
        foreach ($children as $bu) {
            if (!empty($bu['children'])) {
                $result = array_merge($result, $this->getTreeIds($bu['children']));
            }
            $result[] = $bu['id'];
        }

        return $result;
    }

    /**
     * @param int   $businessUnitId
     * @param array $tree
     *
     * @return array
     */
    protected function getBuWithChildTree($businessUnitId, $tree)
    {
        $result = null;
        foreach ($tree as $bu) {
            if (!empty($bu['children'])) {
                $result = $this->getBuWithChildTree($businessUnitId, $bu['children']);
            }
            if ($bu['id'] === $businessUnitId) {
                $result = $bu;
            }

            if ($result) {
                return $result;
            }
        }
    }

    /**
     * @param User                       $currentUser
     * @param int                        $accessLevel
     * @param OwnerTreeProviderInterface $treeProvider
     * @param Organization               $organization
     *
     * @return array
     */
    private function getAllowedBusinessUnitIds(
        UserInterface $currentUser,
        $accessLevel,
        OwnerTreeProviderInterface $treeProvider,
        Organization $organization
    ) {
        if (AccessLevel::LOCAL_LEVEL === $accessLevel) {
            return $treeProvider->getTree()->getUserBusinessUnitIds(
                $currentUser->getId(),
                $organization->getId()
            );
        }

        if (AccessLevel::DEEP_LEVEL === $accessLevel) {
            return $treeProvider->getTree()->getUserSubordinateBusinessUnitIds(
                $currentUser->getId(),
                $organization->getId()
            );
        }

        return [];
    }
}
