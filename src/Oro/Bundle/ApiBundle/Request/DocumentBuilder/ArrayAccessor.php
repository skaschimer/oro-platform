<?php

namespace Oro\Bundle\ApiBundle\Request\DocumentBuilder;

use Oro\Bundle\ApiBundle\Util\ConfigUtil;

/**
 * Provides an access to properties of arrays.
 */
class ArrayAccessor implements ObjectAccessorInterface
{
    #[\Override]
    public function getClassName(mixed $object): ?string
    {
        return $object[ConfigUtil::CLASS_NAME] ?? null;
    }

    #[\Override]
    public function getValue(mixed $object, string $propertyName): mixed
    {
        if (!$this->hasProperty($object, $propertyName)) {
            throw new \OutOfBoundsException(sprintf('The "%s" property does not exist.', $propertyName));
        }

        return $object[$propertyName];
    }

    #[\Override]
    public function hasProperty(mixed $object, string $propertyName): bool
    {
        // ignore "metadata" items
        if (ConfigUtil::CLASS_NAME === $propertyName) {
            return false;
        }

        return \array_key_exists($propertyName, $object);
    }

    #[\Override]
    public function toArray(mixed $object): array
    {
        // remove "metadata" items
        unset($object[ConfigUtil::CLASS_NAME]);

        return $object;
    }
}
