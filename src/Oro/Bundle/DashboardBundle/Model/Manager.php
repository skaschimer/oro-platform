<?php

namespace Oro\Bundle\DashboardBundle\Model;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DashboardBundle\Entity\ActiveDashboard;
use Oro\Bundle\DashboardBundle\Entity\Dashboard;
use Oro\Bundle\DashboardBundle\Entity\Widget;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationAwareTokenInterface;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Manages dashboards.
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class Manager
{
    public function __construct(
        protected Factory $factory,
        protected ManagerRegistry $doctrine,
        protected AclHelper $aclHelper,
        protected TokenStorageInterface $tokenStorage
    ) {
    }

    /**
     * Find dashboard model by id.
     */
    public function findDashboardModel(int $id): ?DashboardModel
    {
        $entity = $this->doctrine->getRepository(Dashboard::class)->find($id);
        if ($entity) {
            return $this->getDashboardModel($entity);
        }

        return null;
    }

    /**
     * Find dashboard model by criteria.
     */
    public function findOneDashboardModelBy(array $criteria, ?array $orderBy = null): ?DashboardModel
    {
        $entity = $this->doctrine->getRepository(Dashboard::class)->findOneBy($criteria, $orderBy);
        if ($entity) {
            return $this->getDashboardModel($entity);
        }

        return null;
    }

    /**
     * Find dashboard widget model by id.
     */
    public function findWidgetModel(int $id): ?WidgetModel
    {
        $entity = $this->doctrine->getRepository(Widget::class)->find($id);
        if ($entity) {
            return $this->getWidgetModel($entity);
        }

        return null;
    }

    /**
     * Get dashboard.
     */
    public function getDashboardModel(Dashboard $entity): DashboardModel
    {
        return $this->factory->createDashboardModel($entity);
    }

    /**
     * Get dashboard widget.
     */
    public function getWidgetModel(Widget $entity): WidgetModel
    {
        return $this->factory->createWidgetModel($entity);
    }

    /**
     * Get all dashboards.
     *
     * @return DashboardModel[]
     */
    public function getDashboardModels(array $entities): array
    {
        $result = [];
        foreach ($entities as $entity) {
            $result[] = $this->getDashboardModel($entity);
        }

        return $result;
    }

    /**
     * Create dashboard.
     */
    public function createDashboardModel(): DashboardModel
    {
        return $this->getDashboardModel(new Dashboard());
    }

    /**
     * Create dashboard widget.
     */
    public function createWidgetModel(string $widgetName): WidgetModel
    {
        $widget = new Widget();

        $widget->setLayoutPosition([0, 0]);
        $widget->setName($widgetName);

        return $this->getWidgetModel($widget);
    }

    public function save(EntityModelInterface $entityModel, bool $flush = false): void
    {
        if ($entityModel instanceof DashboardModel && $entityModel->getStartDashboard() && !$entityModel->getId()) {
            $this->copyWidgets($entityModel, $entityModel->getStartDashboard());
        }

        /** @var EntityManager $entityManager */
        $entityManager = $this->doctrine->getManager();
        $entityManager->persist($entityModel->getEntity());
        if ($flush) {
            $entityManager->flush($entityModel->getEntity());
        }
    }

    public function remove(EntityModelInterface $entityModel): void
    {
        $this->doctrine->getManager()->remove($entityModel->getEntity());
    }

    /**
     * Find active dashboard or default dashboard.
     */
    public function findUserActiveOrDefaultDashboard(User $user): ?DashboardModel
    {
        return $this->findUserActiveDashboard($user) ?? $this->findDefaultDashboard();
    }

    /**
     * Find active dashboard.
     */
    public function findUserActiveDashboard(User $user): ?DashboardModel
    {
        /** @var OrganizationAwareTokenInterface $token */
        $token = $this->tokenStorage->getToken();
        $organization = $token->getOrganization();
        $dashboard = $this->doctrine->getRepository(ActiveDashboard::class)
            ->findOneBy(['user' => $user, 'organization' => $organization]);
        if ($dashboard) {
            return $this->getDashboardModel($dashboard->getDashboard());
        }

        return null;
    }

    /**
     * Find default dashboard.
     */
    public function findDefaultDashboard(): ?DashboardModel
    {
        /** @var OrganizationAwareTokenInterface $token */
        $token = $this->tokenStorage->getToken();
        $organization = $token->getOrganization();
        $dashboard = $this->doctrine->getRepository(Dashboard::class)->findDefaultDashboard($organization);
        if ($dashboard) {
            return $this->getDashboardModel($dashboard);
        }

        return $this->findOneDashboardModelBy(['organization' => $organization]);
    }

    /**
     * @return DashboardModel[]
     */
    public function findAllowedDashboards(string $permission = 'VIEW', ?int $organizationId = null): array
    {
        $qb = $this->doctrine->getRepository(Dashboard::class)->createQueryBuilder('dashboard');
        if ($organizationId) {
            $qb->andWhere($qb->expr()->eq('dashboard.organization', ':organizationId'))
                ->setParameter('organizationId', $organizationId);
        }

        return $this->getDashboardModels($this->aclHelper->apply($qb, $permission)->execute());
    }

    public function findAllowedDashboardsShortenedInfo(string $permission = 'VIEW', ?int $organizationId = null): array
    {
        $qb = $this->doctrine->getRepository(Dashboard::class)
            ->createQueryBuilder('dashboard')
            ->select('dashboard.id, dashboard.label');
        if ($organizationId) {
            $qb->andWhere($qb->expr()->eq('dashboard.organization', ':organizationId'))
                ->setParameter('organizationId', $organizationId);
        }

        return $this->aclHelper->apply($qb, $permission)->execute();
    }

    /**
     * Set current dashboard as active for passed user.
     */
    public function setUserActiveDashboard(DashboardModel $dashboard, User $user, bool $flush = false): void
    {
        /** @var OrganizationAwareTokenInterface $token */
        $token = $this->tokenStorage->getToken();
        $organization = $token->getOrganization();
        $activeDashboard = $this->doctrine->getRepository(ActiveDashboard::class)
            ->findOneBy(['user' => $user, 'organization' => $organization]);

        /** @var EntityManager $entityManager */
        $entityManager = $this->doctrine->getManager();

        if (!$activeDashboard) {
            $activeDashboard = new ActiveDashboard();
            $activeDashboard->setUser($user);
            $activeDashboard->setOrganization($organization);
            $entityManager->persist($activeDashboard);
        }

        $entity = $dashboard->getEntity();
        $activeDashboard->setDashboard($entity);

        if ($flush) {
            $entityManager->flush($activeDashboard);
        }
    }

    /**
     * Copy widgets from source entity to dashboard model.
     */
    protected function copyWidgets(DashboardModel $target, Dashboard $source): void
    {
        foreach ($source->getWidgets() as $sourceWidget) {
            $widgetModel = $this->copyWidgetModel($sourceWidget);
            $this->save($widgetModel);
            $target->addWidget($widgetModel);
        }
    }

    /**
     * Copy widget model by entity.
     */
    protected function copyWidgetModel(Widget $sourceWidget): WidgetModel
    {
        $widget = new Widget();

        $widget->setLayoutPosition($sourceWidget->getLayoutPosition());
        $widget->setName($sourceWidget->getName());
        $widget->setOptions($sourceWidget->getOptions());

        return $this->getWidgetModel($widget);
    }
}
