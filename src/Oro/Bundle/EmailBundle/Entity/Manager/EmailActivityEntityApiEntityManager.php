<?php

namespace Oro\Bundle\EmailBundle\Entity\Manager;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ActivityBundle\Manager\ActivityManager;
use Oro\Bundle\SoapBundle\Entity\Manager\ApiEntityManager;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * The API manager for email activities.
 */
class EmailActivityEntityApiEntityManager extends ApiEntityManager
{
    /** @var ActivityManager */
    protected $activityManager;

    /** @var TokenStorageInterface */
    protected $securityTokenStorage;

    /**
     * @param string                $class
     * @param ObjectManager         $om
     * @param ActivityManager       $activityManager
     * @param TokenStorageInterface $securityTokenStorage
     */
    public function __construct(
        $class,
        ObjectManager $om,
        ActivityManager $activityManager,
        TokenStorageInterface $securityTokenStorage
    ) {
        parent::__construct($class, $om);
        $this->activityManager      = $activityManager;
        $this->securityTokenStorage = $securityTokenStorage;
    }

    #[\Override]
    public function getListQueryBuilder($limit = 10, $page = 1, $criteria = [], $orderBy = null, $joins = [])
    {
        $currentUser = $this->securityTokenStorage->getToken()->getUser();
        $userClass   = ClassUtils::getClass($currentUser);

        return $this->activityManager->getActivityTargetsQueryBuilder(
            $this->class,
            $criteria,
            $joins,
            $limit,
            $page,
            $orderBy,
            function (QueryBuilder $qb, $targetEntityClass) use ($currentUser, $userClass) {
                if ($targetEntityClass === $userClass) {
                    // Need to exclude current user from result because of email context
                    $qb->andWhere(
                        $qb->expr()->neq(
                            QueryBuilderUtil::getSelectExprByAlias($qb, 'entityId'),
                            ':currentUserId'
                        )
                    );
                    $qb->setParameter('currentUserId', $currentUser->getId());
                }
            }
        );
    }
}
