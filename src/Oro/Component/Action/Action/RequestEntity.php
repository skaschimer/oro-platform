<?php

namespace Oro\Component\Action\Action;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\Action\Exception\NotManageableEntityException;
use Oro\Component\ConfigExpression\ContextAccessor;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;
use Symfony\Component\PropertyAccess\PropertyPathInterface;

/**
 * Find an entity based on specified lookup conditions.
 */
class RequestEntity extends AbstractAction
{
    /** @var array */
    protected $options;

    /** @var ManagerRegistry */
    protected $registry;

    public function __construct(ContextAccessor $contextAccessor, ManagerRegistry $registry)
    {
        parent::__construct($contextAccessor);

        $this->registry = $registry;
    }

    #[\Override]
    protected function executeAction($context)
    {
        if (!empty($this->options['identifier'])) {
            $entity = $this->getEntityReference($context);
        } else {
            $entity = $this->getEntityByConditions($context);
        }

        $this->contextAccessor->setValue($context, $this->options['attribute'], $entity);
    }

    #[\Override]
    public function initialize(array $options)
    {
        if (empty($options['class'])) {
            throw new InvalidParameterException('Class name parameter is required');
        }

        if (empty($options['attribute'])) {
            throw new InvalidParameterException('Attribute name parameter is required');
        }
        if (!$options['attribute'] instanceof PropertyPathInterface) {
            throw new InvalidParameterException('Attribute must be valid property definition.');
        }

        $options = $this->validateConditionOptions($options);

        $this->options = $options;

        return $this;
    }

    /**
     * @param array $options
     * @return array
     * @throws InvalidParameterException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function validateConditionOptions(array $options)
    {
        if (empty($options['identifier']) && empty($options['where']) && empty($options['order_by'])) {
            throw new InvalidParameterException(
                'One of parameters "identifier", "where" or "order_by" must be defined'
            );
        }

        if (!empty($options['where']) && !is_array($options['where']) &&
                !$options['where'] instanceof PropertyPathInterface) {
            throw new InvalidParameterException('Parameter "where" must be array or property');
        } elseif (empty($options['where'])) {
            $options['where'] = [];
        }

        if (!empty($options['order_by']) && !is_array($options['order_by'])) {
            throw new InvalidParameterException('Parameter "order_by" must be array');
        } elseif (empty($options['order_by'])) {
            $options['order_by'] = [];
        }

        if (!isset($options['case_insensitive'])) {
            $options['case_insensitive'] = false;
        }

        return $options;
    }

    /**
     * Returns entity proxy for specified entity with specified ID
     *
     * @param mixed $context
     * @return object
     * @throws NotManageableEntityException
     */
    protected function getEntityReference($context)
    {
        $entityClassName = $this->getEntityClassName($context);
        $entityManager = $this->getEntityManager($entityClassName);

        $entityIdentifier = $this->getEntityIdentifier($context);
        $entityIdentifier = $this->applyCaseTransformation($entityIdentifier);
        $entityIdentifier = $this->applyTrim($entityIdentifier);

        return $entityManager->getReference($entityClassName, $entityIdentifier);
    }

    /**
     * Returns entity according to "where" and "order_by" parameters
     *
     * @param mixed $context
     * @return object
     */
    protected function getEntityByConditions($context)
    {
        $entityClassName = $this->getEntityClassName($context);
        $entityManager = $this->getEntityManager($entityClassName);

        $where = $this->getWhere($context);
        $where = $this->applyCaseTransformation($where);
        $where = $this->applyTrim($where);

        $orderBy = $this->getOrderBy($context);
        $orderBy = $this->applyTrim($orderBy);

        $queryBuilder = $entityManager->getRepository($entityClassName)->createQueryBuilder('e');

        // apply where condition
        $counter = 0;
        foreach ($where as $field => $value) {
            QueryBuilderUtil::checkIdentifier($field);
            $parameter = 'parameter_' . $counter;
            $field = 'e.' . $field;
            if ($this->isCaseInsensitive()) {
                $field = "LOWER($field)";
            }
            $queryBuilder->andWhere("$field = :$parameter")->setParameter($parameter, $value);
            $counter++;
        }

        // apply sorting
        foreach ($orderBy as $field => $direction) {
            QueryBuilderUtil::checkIdentifier($field);
            $field = 'e.' . $field;
            $queryBuilder->orderBy($field, QueryBuilderUtil::getSortOrder($direction));
        }
        // should be one result
        $queryBuilder->setMaxResults(1);

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }

    /**
     * @param string $entityClassName
     * @return EntityManager
     * @throws NotManageableEntityException
     */
    protected function getEntityManager($entityClassName)
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->registry->getManagerForClass($entityClassName);
        if (!$entityManager) {
            throw new NotManageableEntityException($entityClassName);
        }

        return $entityManager;
    }

    /**
     * @param mixed $context
     * @return string
     */
    protected function getEntityClassName($context)
    {
        $class = $this->options['class'];

        return $this->contextAccessor->getValue($context, $class);
    }

    /**
     * @param mixed $context
     * @return int|string|array
     */
    protected function getEntityIdentifier($context)
    {
        $identifier = $this->options['identifier'];

        if (is_array($identifier)) {
            $identifier = $this->parseArrayValues($context, $identifier);
        } else {
            $identifier = $this->contextAccessor->getValue($context, $identifier);
        }

        return $identifier;
    }

    /**
     * @param mixed $context
     * @return array
     */
    protected function getWhere($context)
    {
        $where = $this->options['where'];

        if (is_array($where)) {
            $where = $this->parseArrayValues($context, $where);
        } else {
            $where = $this->contextAccessor->getValue($context, $where);
        }

        return $where;
    }

    /**
     * @param mixed $context
     * @return array
     */
    protected function getOrderBy($context)
    {
        return $this->parseArrayValues($context, $this->options['order_by']);
    }

    /**
     * @return bool
     */
    protected function isCaseInsensitive()
    {
        return (bool)$this->options['case_insensitive'];
    }

    /**
     * @param mixed $context
     * @param array $data
     * @return array
     */
    protected function parseArrayValues($context, array $data)
    {
        foreach ($data as $key => $value) {
            $data[$key] = $this->contextAccessor->getValue($context, $value);
        }

        return $data;
    }

    /**
     * @param mixed $data
     * @return mixed
     */
    protected function applyCaseTransformation($data)
    {
        if (!$this->isCaseInsensitive()) {
            return $data;
        }

        if (is_array($data)) {
            foreach ($data as $key => $value) {
                if (is_string($value)) {
                    $data[$key] = mb_strtolower($value);
                }
            }
        } elseif (is_string($data)) {
            $data = mb_strtolower($data);
        }

        return $data;
    }

    /**
     * @param $data
     * @return array|string
     */
    protected function applyTrim($data)
    {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                if (is_scalar($value)) {
                    $data[$key] = is_bool($value) ? $value : trim($value);
                }
            }
        } elseif (is_string($data)) {
            $data = trim($data);
        }

        return $data;
    }
}
