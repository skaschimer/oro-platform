<?php

namespace Oro\Component\Testing\Unit\Constraint;

/**
 * Constraint that asserts that a get*() or is*() getter returns default property value.
 */
class PropertyGetterReturnsDefaultValue extends \PHPUnit\Framework\Constraint\Constraint
{
    /**
     * @var string
     */
    private $getterName;

    /**
     * @var string
     */
    private $propertyName;

    /**
     * @param string $propertyName
     */
    public function __construct($propertyName)
    {
        $this->propertyName = $propertyName;
    }

    #[\Override]
    public function toString(): string
    {
        return sprintf(
            'getter %s for property %s returns the default value',
            $this->getterName,
            $this->propertyName
        );
    }
    #[\Override]
    protected function failureDescription($other): string
    {
        return sprintf(
            'getter %s for property %s of class %s returns the default value',
            $this->getterName,
            $this->propertyName,
            get_class($other)
        );
    }

    #[\Override]
    protected function matches($other): bool
    {
        $this->getterName = 'get' . ucfirst($this->propertyName);
        if (!method_exists($other, $this->getterName)) {
            $this->getterName = 'is' . ucfirst($this->propertyName);
        }
        if (!method_exists($other, $this->getterName)) {
            $message = sprintf(
                "Class %s doesn't have %s() or %s() getters for property %s",
                get_class($other),
                'get' . ucfirst($this->propertyName),
                'is' . ucfirst($this->propertyName),
                $this->propertyName
            );
            throw new \PHPUnit\Framework\Exception($message);
        }

        $class = new \ReflectionClass($other);
        $defaultProperties = $class->getDefaultProperties();

        return call_user_func_array([$other, $this->getterName], []) === $defaultProperties[$this->propertyName];
    }
}
