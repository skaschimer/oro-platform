<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Element\Transformers;

class PageSuffixTransformer implements NamePartsTransformerInterface
{
    #[\Override]
    public function isApplicable(array $nameParts)
    {
        return strtolower(end($nameParts)) !== 'page';
    }

    #[\Override]
    public function transform(array $nameParts)
    {
        return $nameParts + [count($nameParts) => 'page'];
    }
}
