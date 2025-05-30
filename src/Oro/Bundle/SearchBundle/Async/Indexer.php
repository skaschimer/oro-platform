<?php

namespace Oro\Bundle\SearchBundle\Async;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SearchBundle\Async\Topic\IndexEntitiesByIdTopic;
use Oro\Bundle\SearchBundle\Async\Topic\ReindexTopic;
use Oro\Bundle\SearchBundle\Engine\IndexerInterface;
use Oro\Bundle\SearchBundle\Transformer\MessageTransformerInterface;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

/**
 * Performs indexation operations for search index.
 */
class Indexer implements IndexerInterface
{
    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var MessageTransformerInterface
     */
    private $transformer;

    /**
     * @var MessageProducerInterface
     */
    protected $producer;

    public function __construct(
        MessageProducerInterface $producer,
        DoctrineHelper $doctrineHelper,
        MessageTransformerInterface $transformer
    ) {
        $this->producer = $producer;
        $this->doctrineHelper = $doctrineHelper;
        $this->transformer = $transformer;
    }

    #[\Override]
    public function save($entity, array $context = [])
    {
        return $this->doIndex($entity);
    }

    #[\Override]
    public function delete($entity, array $context = [])
    {
        return $this->doIndex($entity);
    }

    #[\Override]
    public function resetIndex($class = null, array $context = [])
    {
        throw new \LogicException('Method is not implemented');
    }

    #[\Override]
    public function getClassesForReindex($class = null, array $context = [])
    {
        throw new \LogicException('Method is not implemented');
    }

    #[\Override]
    public function reindex($class = null, array $context = [])
    {
        if (is_array($class)) {
            $classes = $class;
        } else {
            $classes = $class ? [$class] : [];
        }

        //Ensure specified class exists, if not - exception will be thrown
        foreach ($classes as $class) {
            $this->doctrineHelper->getEntityManagerForClass($class);
        }

        $this->producer->send(ReindexTopic::getName(), $classes);
    }

    /**
     * @param string|array $entity
     *
     * @return bool
     */
    protected function doIndex($entity)
    {
        if (!$entity) {
            return false;
        }

        $entities = is_array($entity) ? $entity : [$entity];

        $messages = $this->transformer->transform($entities);
        foreach ($messages as $message) {
            $this->producer->send(IndexEntitiesByIdTopic::getName(), $message);
        }

        return true;
    }
}
