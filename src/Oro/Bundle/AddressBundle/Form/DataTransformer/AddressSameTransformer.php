<?php

namespace Oro\Bundle\AddressBundle\Form\DataTransformer;

use Oro\Bundle\AddressBundle\Entity\Address;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Address same transformer.
 */
class AddressSameTransformer implements DataTransformerInterface
{
    /** @var array */
    private $fields = [];

    /** @var PropertyAccessorInterface */
    private $propertyAccessor;

    /** @var array */
    private $ids = [];

    public function __construct(PropertyAccessorInterface $propertyAccessor, array $fields)
    {
        $this->propertyAccessor = $propertyAccessor;
        $this->fields = $fields;
    }

    #[\Override]
    public function transform($value)
    {
        if ($value === null) {
            return $value;
        }

        foreach ($this->fields as $field) {
            $address = $this->propertyAccessor->getValue($value, $field);
            if ($address instanceof Address && $address->getId()) {
                if (in_array($address->getId(), $this->ids, null)) {
                    $address = clone $address;
                    $this->propertyAccessor->setValue($value, $field, $address);
                } else {
                    $this->ids[] = $address->getId();
                }
            }
        }

        return $value;
    }

    #[\Override]
    public function reverseTransform($value)
    {
        return $value;
    }
}
