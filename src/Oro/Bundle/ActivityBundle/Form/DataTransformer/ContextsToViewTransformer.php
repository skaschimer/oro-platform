<?php

namespace Oro\Bundle\ActivityBundle\Form\DataTransformer;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Transforms activity contexts to the form view format
 */
class ContextsToViewTransformer implements DataTransformerInterface
{
    const SEPARATOR = '-|-';

    /** @var EntityManagerInterface */
    protected $entityManager;

    /** @var TranslatorInterface */
    protected $translator;

    /** @var bool */
    protected $collectionModel;

    /** @var string */
    protected $separator = self::SEPARATOR;

    /**
     * @param EntityManagerInterface $entityManager
     * @param bool                   $collectionModel True if result should be Collection instead of array
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        $collectionModel = false
    ) {
        $this->entityManager = $entityManager;
        $this->collectionModel = $collectionModel;
    }

    /**
     * @param string $separator
     */
    public function setSeparator($separator)
    {
        $this->separator = $separator;
    }

    #[\Override]
    public function transform($value)
    {
        if (!$value) {
            return '';
        }

        if (is_array($value) || $value instanceof Collection) {
            $result = [];
            foreach ($value as $target) {
                $targetClass = ClassUtils::getClass($target);

                $result[] = json_encode(
                    [
                        'entityClass' => $targetClass,
                        'entityId'    => $target->getId(),
                    ]
                );
            }

            $value = implode($this->separator, $result);
        }

        return $value;
    }

    #[\Override]
    public function reverseTransform($value)
    {
        if (!$value) {
            return [];
        }

        $targets = explode($this->separator, $value);
        $result  = [];
        $filters = [];

        foreach ($targets as $target) {
            $target = json_decode($target, true);
            if (array_key_exists('entityClass', $target) === true && array_key_exists('entityId', $target)) {
                if (!isset($filters[$target['entityClass']])) {
                    $filters[$target['entityClass']] = [];
                }
                $filters[$target['entityClass']][] = $target['entityId'];
            }
        }

        foreach ($filters as $entityClass => $ids) {
            $metadata = $this->entityManager->getClassMetadata($entityClass);
            $entities = $this->entityManager->getRepository($metadata->getName())->findBy(
                ['id' => $ids]
            );
            $result   = array_merge($result, $entities);
        }

        if ($this->collectionModel) {
            $result = new ArrayCollection($result);
        }

        return $result;
    }
}
