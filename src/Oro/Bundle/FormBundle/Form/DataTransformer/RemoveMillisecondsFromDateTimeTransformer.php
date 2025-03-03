<?php

namespace Oro\Bundle\FormBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

/**
 * Removes milliseconds from a string representation of datetime.
 * @see \Oro\Bundle\FormBundle\Form\Extension\DateTimeExtension::buildForm
 */
class RemoveMillisecondsFromDateTimeTransformer implements DataTransformerInterface
{
    private const RFC_3339_PATTERN = '/^(\d+-\d+-\d+T\d+:\d+(?::\d+)?)(\.\d+)(Z|(?:(?:\+|-)\d{2}:\d{2}))$/';

    /** @var DataTransformerInterface */
    private $innerTransformer;

    public function __construct(DataTransformerInterface $innerTransformer)
    {
        $this->innerTransformer = $innerTransformer;
    }

    #[\Override]
    public function transform($value)
    {
        return $this->innerTransformer->transform($value);
    }

    #[\Override]
    public function reverseTransform($value)
    {
        if (is_string($value) && $value && preg_match(self::RFC_3339_PATTERN, $value, $matches)) {
            $value = $matches[1] . $matches[3];
        }

        return $this->innerTransformer->reverseTransform($value);
    }
}
