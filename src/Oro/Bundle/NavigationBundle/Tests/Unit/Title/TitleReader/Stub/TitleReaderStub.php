<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Title\TitleReader\Stub;

use Oro\Bundle\NavigationBundle\Title\TitleReader\ReaderInterface;

class TitleReaderStub implements ReaderInterface
{
    /** @var array */
    private $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    #[\Override]
    public function getTitle($route)
    {
        return array_key_exists($route, $this->data) ? $this->data[$route] : null;
    }
}
