<?php

namespace Oro\Bundle\DataGridBundle\Extension\MassAction;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;
use Oro\Bundle\DataGridBundle\Datasource\Orm\IterableResult;
use Oro\Bundle\DataGridBundle\Datasource\Orm\IterableResultInterface;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Exception\LogicException;
use Oro\Bundle\DataGridBundle\Extension\Action\ActionConfiguration;
use Oro\Bundle\DataGridBundle\Extension\MassAction\DTO\SelectedItems;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

/**
 * Creates IterableResultInterace for orm datasouce to pass to mass action handler through params.
 */
class IterableResultFactory implements IterableResultFactoryInterface
{
    /**
     * @var AclHelper
     */
    private $aclHelper;

    public function __construct(AclHelper $aclHelper)
    {
        $this->aclHelper = $aclHelper;
    }

    #[\Override]
    public function isApplicable(DatasourceInterface $dataSource): bool
    {
        return $dataSource instanceof OrmDatasource;
    }

    #[\Override]
    public function createIterableResult(
        DatasourceInterface $dataSource,
        ActionConfiguration $actionConfiguration,
        DatagridConfiguration $gridConfiguration,
        SelectedItems $selectedItems
    ): IterableResultInterface {
        /** @var OrmDatasource $dataSource */
        if (!$this->isApplicable($dataSource)) {
            throw new LogicException(
                sprintf('Expecting "%s" datasource type, "%s" given', OrmDatasource::class, get_class($dataSource))
            );
        }

        /** @var QueryBuilder $qb */
        $qb = $dataSource->getQueryBuilder();

        //prepare query builder
        $qb->setMaxResults(null);
        $qb->setFirstResult(null);

        $identifierField = $this->getIdentifierField($actionConfiguration);
        $objectIdentifier = $this->getObjectIdentifier($actionConfiguration);
        //Query may have aggregation and sort criteria by non aggregated columns
        //which will cause fatal error, so let's replace it to the predictable criteria
        $qb->orderBy($identifierField, Criteria::ASC);

        if ($selectedItems->getValues()) {
            $valueWhereCondition =
                $selectedItems->isInset()
                    ? $qb->expr()->in($identifierField, ':values')
                    : $qb->expr()->notIn($identifierField, ':values');
            $qb->andWhere($valueWhereCondition);
            $qb->setParameter('values', $selectedItems->getValues());
        }

        if (!$this->isIdentifierFieldPresent($qb, $identifierField)) {
            $qb->addSelect($identifierField);
            $qb->addGroupBy($identifierField);
        }

        if ($objectIdentifier) {
            $qb->addSelect($objectIdentifier);
        }

        if (!$gridConfiguration->isDatasourceSkipAclApply()) {
            $qb = $this->aclHelper->apply($qb);
        }

        return $this->getIterableResult($qb);
    }

    private function isIdentifierFieldPresent(QueryBuilder $queryBuilder, string $identifierField): bool
    {
        $identifierFieldFound = false;
        /** @var Expr\Select[] $selectDqlParts */
        $selectDqlParts = $queryBuilder->getDQLPart('select');
        $as = ' as ';
        foreach ($selectDqlParts as $selectDqlPart) {
            foreach ($selectDqlPart->getParts() as $part) {
                if (in_array($identifierField, explode($as, str_ireplace($as, $as, $part)), true)) {
                    $identifierFieldFound = true;
                    break;
                }
            }
        }

        return $identifierFieldFound;
    }

    /**
     * @param ActionConfiguration $actionConfiguration
     * @return string
     */
    private function getIdentifierField(ActionConfiguration $actionConfiguration)
    {
        $identifier = $actionConfiguration->offsetGetOr('data_identifier');
        if (!$identifier) {
            throw new LogicException('Mass action must define identifier name');
        }
        QueryBuilderUtil::checkField($identifier);

        return $identifier;
    }

    /**
     * @param ActionConfiguration $actionConfiguration
     * @return null|string
     */
    private function getObjectIdentifier(ActionConfiguration $actionConfiguration)
    {
        $identifier = $actionConfiguration->offsetGetOr('object_identifier');
        QueryBuilderUtil::checkIdentifier($identifier);

        return $identifier;
    }

    /**
     * @param Query|QueryBuilder $qb
     * @return IterableResult
     */
    protected function getIterableResult($qb): IterableResult
    {
        return new IterableResult($qb);
    }
}
