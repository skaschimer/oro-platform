<?php

namespace Oro\Bundle\EntityBundle\Provider;

use Doctrine\ORM\Mapping\ClassMetadata;

/**
 * Delegates a work to child ExclusionProviderInterface providers.
 */
class ChainExclusionProvider implements ExclusionProviderInterface
{
    /**
     * @var ExclusionProviderInterface[]
     */
    protected array $providers = [];

    /**
     * @param iterable<ExclusionProviderInterface> $providers
     */
    public function __construct(iterable $providers = [])
    {
        $this->providers = $providers instanceof \Traversable ? iterator_to_array($providers) : $providers;
    }

    /**
     * Registers the given provider in the chain
     */
    public function addProvider(ExclusionProviderInterface $provider)
    {
        $this->providers[] = $provider;
    }

    #[\Override]
    public function isIgnoredEntity($className)
    {
        foreach ($this->providers as $provider) {
            if ($provider->isIgnoredEntity($className)) {
                return true;
            }
        }

        return false;
    }

    #[\Override]
    public function isIgnoredField(ClassMetadata $metadata, $fieldName)
    {
        foreach ($this->providers as $provider) {
            if ($provider->isIgnoredField($metadata, $fieldName)) {
                return true;
            }
        }

        return false;
    }

    #[\Override]
    public function isIgnoredRelation(ClassMetadata $metadata, $associationName)
    {
        foreach ($this->providers as $provider) {
            if ($provider->isIgnoredRelation($metadata, $associationName)) {
                return true;
            }
        }

        return false;
    }
}
