<?php

namespace Oro\Bundle\FormBundle\Form\DataTransformer;

use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Exception\UnexpectedTypeException;

/**
 * Transforms between array of entities and array of ids
 */
class EntitiesToIdsTransformer extends EntityToIdTransformer
{
    #[\Override]
    public function transform($value)
    {
        if (null === $value || [] === $value) {
            return [];
        }

        if (!is_array($value) && !$value instanceof \Traversable) {
            throw new UnexpectedTypeException($value, 'array');
        }

        $result = [];
        foreach ($value as $entity) {
            $id = $this->propertyAccessor->getValue($entity, $this->propertyPath);
            $result[] = $id;
        }

        return $result;
    }

    #[\Override]
    public function reverseTransform($value)
    {
        if (!$value) {
            return [];
        }

        if (!is_array($value) && !$value instanceof \Traversable) {
            throw new UnexpectedTypeException($value, 'array');
        }

        return $this->loadEntitiesByIds($value);
    }

    /**
     * Load entities by array of ids
     *
     * @param array $ids
     * @return array
     * @throws UnexpectedTypeException if query builder callback returns invalid type
     * @throws TransformationFailedException if values not matched given $ids
     */
    protected function loadEntitiesByIds(array $ids)
    {
        $repository = $this->em->getRepository($this->className);
        if ($this->queryBuilderCallback) {
            /** @var $qb QueryBuilder */
            $qb = call_user_func($this->queryBuilderCallback, $repository, $ids);
            if (!$qb instanceof QueryBuilder) {
                throw new UnexpectedTypeException($qb, 'Doctrine\ORM\QueryBuilder');
            }
        } else {
            $qb = $repository->createQueryBuilder('e');
            $qb->where(sprintf('e.%s IN (:ids)', $this->propertyPath))
                ->setParameter('ids', $ids);
        }

        $result = $qb->getQuery()->execute();

        if (count($result) !== count($ids)) {
            throw new TransformationFailedException('Could not find all entities for the given IDs');
        }

        return $result;
    }
}
