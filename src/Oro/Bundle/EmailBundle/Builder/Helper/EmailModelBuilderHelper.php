<?php

namespace Oro\Bundle\EmailBundle\Builder\Helper;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\EmailBundle\Cache\EmailCacheManager;
use Oro\Bundle\EmailBundle\Entity\Email as EmailEntity;
use Oro\Bundle\EmailBundle\Entity\EmailOwnerAwareInterface;
use Oro\Bundle\EmailBundle\Entity\EmailOwnerInterface;
use Oro\Bundle\EmailBundle\Entity\Mailbox;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailAddressManager;
use Oro\Bundle\EmailBundle\Entity\Manager\MailboxManager;
use Oro\Bundle\EmailBundle\Exception\LoadEmailBodyException;
use Oro\Bundle\EmailBundle\Model\EmailHolderInterface;
use Oro\Bundle\EmailBundle\Tools\EmailAddressHelper;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\Security\Core\User\UserInterface;
use Twig\Environment;

/**
 * Provides helper methods for building full email address and user email related methods.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class EmailModelBuilderHelper
{
    public function __construct(
        protected EntityRoutingHelper $entityRoutingHelper,
        protected EmailAddressHelper $emailAddressHelper,
        protected EntityNameResolver $entityNameResolver,
        protected TokenAccessorInterface $tokenAccessor,
        protected EmailAddressManager $emailAddressManager,
        protected EmailCacheManager $emailCacheManager,
        protected Environment $twig,
        protected MailboxManager $mailboxManager
    ) {
    }

    /**
     * @param string      $emailAddress
     * @param string|null $ownerClass
     * @param mixed|null  $ownerId
     * @param bool        $excludeCurrentUser
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function preciseFullEmailAddress(
        &$emailAddress,
        $ownerClass = null,
        $ownerId = null,
        $excludeCurrentUser = false
    ) {
        if (!$this->emailAddressHelper->isFullEmailAddress($emailAddress)) {
            if (!empty($ownerClass) && !empty($ownerId)) {
                $owner = $this->entityRoutingHelper->getEntity($ownerClass, $ownerId);
                if ($owner) {
                    if ($this->doExcludeCurrentUser($excludeCurrentUser, $emailAddress, $owner)) {
                        return;
                    }
                    if ($owner instanceof EmailOwnerAwareInterface) {
                        $ownerName = $this->entityNameResolver->getName($owner->getEmailOwner());
                    } else {
                        $ownerName = $this->entityNameResolver->getName($owner);
                    }
                    if (!empty($ownerName)) {
                        $emailAddress = $this->emailAddressHelper->buildFullEmailAddress($emailAddress, $ownerName);

                        return;
                    }
                }
            }
            $repo = $this->emailAddressManager->getEmailAddressRepository();
            $emailAddressObj = $repo->findOneBy(['email' => $emailAddress]);
            if ($emailAddressObj) {
                $owner = $emailAddressObj->getOwner();
                if ($owner) {
                    if ($this->doExcludeCurrentUser($excludeCurrentUser, $emailAddress, $owner)) {
                        return;
                    }
                    $ownerName = $this->entityNameResolver->getName($owner);
                    if (!empty($ownerName)) {
                        $emailAddress = $this->emailAddressHelper->buildFullEmailAddress($emailAddress, $ownerName);
                    }
                }
            }
        }
    }

    /**
     * @param bool $excludeCurrentUser
     * @param string $emailAddress
     * @param object $owner
     * @return bool
     */
    protected function doExcludeCurrentUser($excludeCurrentUser, &$emailAddress, $owner)
    {
        if (!$excludeCurrentUser) {
            return false;
        }
        $user = $this->getUser();
        if (ClassUtils::getClass($owner) === ClassUtils::getClass($user) && $owner->getId() === $user->getId()) {
            $emailAddress = false;

            return true;
        }

        return false;
    }

    /**
     * Get the current authenticated user
     *
     * @return User|UserInterface|EmailHolderInterface|EmailOwnerInterface|null
     */
    public function getUser()
    {
        return $this->tokenAccessor->getUser();
    }

    /**
     * Get current organization
     *
     * @return Organization
     */
    public function getOrganization()
    {
        return $this->tokenAccessor->getOrganization();
    }

    public function ensureEmailBodyCached(EmailEntity $emailEntity)
    {
        $this->emailCacheManager->ensureEmailBodyCached($emailEntity);
    }

    /**
     * @param string $className
     *
     * @return string
     */
    public function decodeClassName($className)
    {
        return $this->entityRoutingHelper->resolveEntityClass($className);
    }

    /**
     * @param User $user
     *
     * @return string
     */
    public function buildFullEmailAddress(User $user)
    {
        return $this->emailAddressHelper->buildFullEmailAddress(
            $user->getEmail(),
            $this->entityNameResolver->getName($user)
        );
    }

    /**
     * @param EmailEntity $emailEntity
     * @param             $templatePath
     *
     * @return null|string
     */
    public function getEmailBody(EmailEntity $emailEntity, $templatePath)
    {
        try {
            $this->emailCacheManager->ensureEmailBodyCached($emailEntity);
        } catch (LoadEmailBodyException $e) {
            return null;
        }

        return $this->twig->render($templatePath, ['email' => $emailEntity]);
    }

    /**
     * @param $prefix
     * @param $subject
     *
     * @return string
     */
    public function prependWith($prefix, $subject)
    {
        if (!preg_match('/^' . $prefix . ':*/', $subject)) {
            return $prefix . $subject;
        }
        return $subject;
    }

    /**
     * @param string $entityClass
     * @param string $entityId
     * @return object
     */
    public function getTargetEntity($entityClass, $entityId)
    {
        return $this->entityRoutingHelper->getEntity($entityClass, $entityId);
    }

    /**
     * Returns mailboxes available to currently logged in user.
     *
     * @return Mailbox[]
     */
    public function getMailboxes()
    {
        $mailboxes = $this->mailboxManager->findAvailableMailboxes(
            $this->getUser(),
            $this->getOrganization()
        );

        return $mailboxes;
    }

    /**
     * @param $entity
     *
     * @return bool
     */
    protected function isFullQualifiedUser($entity)
    {
        return $entity instanceof UserInterface
        && $entity instanceof EmailHolderInterface
        && $entity instanceof EmailOwnerInterface;
    }
}
