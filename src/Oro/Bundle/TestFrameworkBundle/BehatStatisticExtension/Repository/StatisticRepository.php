<?php

namespace Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Repository;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Model\StatisticModelInterface;
use Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Repository\AvgStrategy\AvgStrategyAwareInterface;
use Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Repository\AvgStrategy\AvgStrategyInterface;

/**
 * Doctrine repository for working with Statistic model.
 */
class StatisticRepository implements BatchRepositoryInterface, ObjectRepository, AvgStrategyAwareInterface
{
    const MAX_LIMIT = 10000;

    protected Connection $connection;

    /**
     * @var StatisticModelInterface[]
     */
    protected array $collection = [];

    /**
     * @var string|StatisticModelInterface
     */
    protected $className;

    protected AvgStrategyInterface $avgStrategy;

    private array $paths;

    /**
     * StatisticRepository constructor.
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    #[\Override]
    public function add(StatisticModelInterface $model)
    {
        $this->collection[] = $model;
    }

    /**
     * @param int $numberOfBuilds
     * @param array $criteria
     * @return array
     */
    public function getLastBuildIds($numberOfBuilds, array $criteria)
    {
        $buildIdsQueryBuilder = $this->connection->createQueryBuilder()
            ->select('build_id')
            ->from($this->className::getName())
            ->groupBy('build_id')
            ->orderBy('build_id', 'DESC')
            ->setMaxResults($numberOfBuilds)
        ;

        if ($criteria) {
            $this->addCriteria($criteria, $buildIdsQueryBuilder);
        }
        $ids = $buildIdsQueryBuilder->execute()->fetchAllAssociative();

        $ids = array_map(function ($data) {
            return $data['build_id'];
        }, $ids);
        $ids = array_filter($ids);

        return $ids;
    }

    /**
     * @param array $criteria
     * @return array [ID:string|int => Time:int]
     */
    public function getAverageTimeTable(array $criteria)
    {
        $queryBuilder = $this->connection->createQueryBuilder()
            ->select($this->className::getIdField())
            ->from($this->className::getName())
            ->groupBy($this->className::getIdField())
        ;

        $this->avgStrategy->addSelect($queryBuilder);

        if ($criteria) {
            $this->addCriteria($criteria, $queryBuilder);
        }

        $result = $queryBuilder->execute()->fetchAllAssociative();

        $paths = [];

        foreach ($result as $row) {
            $paths[$row[$this->className::getIdField()]] = round($row[AvgStrategyInterface::TIME_FIELD_NAME]);
        }

        return $this->paths = $paths;
    }

    #[\Override]
    public function flush()
    {
        $this->connection->connect();

        foreach ($this->collection as $model) {
            if (!$model->isNew()) {
                continue;
            }

            $this->connection->insert($model::getName(), $model->toArray());
        }

        $this->connection->close();
        $this->collection = [];
    }

    #[\Override]
    public function findBy(array $criteria, ?array $orderBy = null, $limit = null, $offset = null)
    {
        $orderBy = $orderBy ?: ['id', 'DESC'];
        $limit = $limit ?: self::MAX_LIMIT;

        if ($limit > self::MAX_LIMIT) {
            throw new \RuntimeException(sprintf('You should not set limit over then %s', self::MAX_LIMIT));
        }

        $queryBuilder = $this->connection->createQueryBuilder()
            ->select('*')
            ->from($this->className::getName())
        ;

        $this->addCriteria($criteria, $queryBuilder);

        if ($orderBy) {
            $queryBuilder->orderBy($orderBy[0], $orderBy[1]);
        }

        if ($limit) {
            $queryBuilder->setMaxResults($limit);
        }

        if ($offset) {
            $queryBuilder->setFirstResult($offset);
        }

        $result = $queryBuilder->execute()
            ->fetchAllAssociative()
        ;

        $models = array_map(function (array $data) {
            $model = $this->className::fromArray($data);
            $this->collection[$model->getPath()] = $model;

            return $model;
        }, $result);

        return $models;
    }

    #[\Override]
    public function find($id)
    {
        if (isset($this->collection[$id])) {
            return $this->collection[$id];
        }

        $result = $this->connection->createQueryBuilder()
            ->select('*')
            ->from($this->className::getName())
            ->where($this->className::getIdField().' = ?')
            ->setParameter(0, $id)
            ->orderBy('id', 'DESC')
            ->execute()
            ->fetchAssociative()
        ;

        if (!$result) {
            return new $this->className();
        }

        $model = $this->className::fromArray($result);
        $this->collection[$model->getPath()] = $model;

        return $model;
    }

    /**
     * @param int $lifetime
     * @return int
     */
    public function removeOldStatistics(int $lifetime)
    {
        $date = new \DateTime('now', new \DateTimeZone('UTC'));
        $date->modify(sprintf('-%d seconds', $lifetime));

        $qb = $this->connection->createQueryBuilder();
        $qb->delete($this->className::getName())
            ->where(
                $qb->expr()->orX(
                    $qb->expr()->lt('created_at', '?'),
                    $qb->expr()->isNull('created_at')
                )
            )
            ->setParameter(0, $date->format('Y-m-d H:i:s'));

        return $qb->execute();
    }

    #[\Override]
    public function findAll()
    {
        throw new \RuntimeException('It\'s prohibited call "findAll" on statistics. Use "findBy" method');
    }

    #[\Override]
    public function findOneBy(array $criteria)
    {
        throw new \RuntimeException('Not implemented');
    }

    #[\Override]
    public function getClassName()
    {
        return $this->className;
    }

    /**
     * @param string $className
     */
    public function setClassName($className)
    {
        $this->className = $className;
    }

    #[\Override]
    public function setAvgStrategy(AvgStrategyInterface $avgStrategy)
    {
        $this->avgStrategy = $avgStrategy;
    }

    /**
     * @param array $criteria
     * @param QueryBuilder $queryBuilder
     * @return void
     */
    private function addCriteria(array $criteria, QueryBuilder $queryBuilder)
    {
        $andExpr = $queryBuilder->expr()->andX();

        foreach ($criteria as $field => $value) {
            if (is_null($value)) {
                $andExpr->add($queryBuilder->expr()->isNull($field));
            } elseif (is_array($value)) {
                $andExpr->add($queryBuilder->expr()->in($field, $value));
            } else {
                $valueKey = uniqid(':where_value_');
                $andExpr->add($queryBuilder->expr()->eq($field, $valueKey));
                $queryBuilder->setParameter($valueKey, $value);
            }
        }

        $queryBuilder->andWhere($andExpr);
    }
}
