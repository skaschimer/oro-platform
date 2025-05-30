<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Log\Processor\Stub;

use Oro\Component\MessageQueue\Consumption\AbstractExtension;
use Oro\Component\MessageQueue\Consumption\ExtensionInterface;
use ProxyManager\Proxy\ValueHolderInterface;

class ExtensionProxy extends AbstractExtension implements ValueHolderInterface
{
    /** @var ExtensionInterface */
    private $extension;

    public function __construct(ExtensionInterface $extension)
    {
        $this->extension = $extension;
    }

    #[\Override]
    public function getWrappedValueHolderValue(): ?object
    {
        return $this->extension;
    }
}
