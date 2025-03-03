<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\Select2Type;
use Symfony\Component\Form\AbstractExtension;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

class TestFormExtension extends AbstractExtension
{
    #[\Override]
    protected function loadTypes(): array
    {
        return [
            new Select2Type(HiddenType::class, 'oro_select2_hidden')
        ];
    }
}
