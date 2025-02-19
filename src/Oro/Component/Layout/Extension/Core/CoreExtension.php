<?php

namespace Oro\Component\Layout\Extension\Core;

use Oro\Component\Layout\Block\Extension as TypeExtension;
use Oro\Component\Layout\Block\Type;
use Oro\Component\Layout\Extension\AbstractExtension;

class CoreExtension extends AbstractExtension
{
    #[\Override]
    protected function loadTypes()
    {
        return [
            new Type\BaseType(),
            new Type\ContainerType()
        ];
    }

    #[\Override]
    protected function loadTypeExtensions()
    {
        return [
            new TypeExtension\ClassAttributeExtension()
        ];
    }
}
