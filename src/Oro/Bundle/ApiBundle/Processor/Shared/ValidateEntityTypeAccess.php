<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\SecurityBundle\Acl\Extension\EntityAclExtension;
use Oro\Bundle\SecurityBundle\Acl\Extension\ObjectIdentityHelper;
use Oro\Bundle\SecurityBundle\Acl\Group\AclGroupProviderInterface;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Validates whether an access to the type of entities specified
 * in the "class" property of the context is granted.
 * The permission type is provided in $permission argument of the class constructor.
 */
class ValidateEntityTypeAccess implements ProcessorInterface
{
    private AuthorizationCheckerInterface $authorizationChecker;
    private DoctrineHelper $doctrineHelper;
    private AclGroupProviderInterface $aclGroupProvider;
    private string $permission;
    private bool $forcePermissionUsage;

    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        DoctrineHelper $doctrineHelper,
        AclGroupProviderInterface $aclGroupProvider,
        string $permission,
        bool $forcePermissionUsage = false
    ) {
        $this->authorizationChecker = $authorizationChecker;
        $this->doctrineHelper = $doctrineHelper;
        $this->aclGroupProvider = $aclGroupProvider;
        $this->permission = $permission;
        $this->forcePermissionUsage = $forcePermissionUsage;
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var Context $context */

        $isGranted = true;
        $config = $context->getConfig();
        if (null !== $config && $config->hasAclResource()) {
            $aclResource = $config->getAclResource();
            if ($aclResource) {
                if ($this->forcePermissionUsage) {
                    $isGranted = $this->isGrantedForClass($context);
                } else {
                    $isGranted = $this->authorizationChecker->isGranted($aclResource);
                }
            }
        } else {
            $isGranted = $this->isGrantedForClass($context);
        }

        if (!$isGranted) {
            throw new AccessDeniedException('No access to this type of entities.');
        }
    }

    private function isGrantedForClass(Context $context): bool
    {
        $isGranted = true;

        $className = $context->getManageableEntityClass($this->doctrineHelper);
        if ($className) {
            $isGranted = $this->authorizationChecker->isGranted(
                $this->permission,
                ObjectIdentityHelper::encodeIdentityString(
                    EntityAclExtension::NAME,
                    ObjectIdentityHelper::buildType($className, $this->aclGroupProvider->getGroup())
                )
            );
        }

        return $isGranted;
    }
}
