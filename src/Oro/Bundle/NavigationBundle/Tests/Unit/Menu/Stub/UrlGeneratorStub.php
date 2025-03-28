<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Menu\Stub;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;

class UrlGeneratorStub implements UrlGeneratorInterface
{
    /**
     * @var array
     */
    private static $compiledRoutes = [
        'route_name' => [
            ['view'],
            ['_controller' => 'controller::action'],
            ['_method' => 'GET'],
            [['text', '/baz']],
            [],
            []
        ],
        'test' => [
            ['view'],
            ['_controller' => 'action'],
            ['_method' => 'GET'],
            [['text', '/baz']],
            [],
            []
        ]
    ];

    /**
     * @var RequestContext
     */
    private $context;

    #[\Override]
    public function generate($name, $parameters = [], $referenceType = self::ABSOLUTE_PATH): string
    {
        return [];
    }

    #[\Override]
    public function setContext(RequestContext $context)
    {
        $this->context = $context;
    }

    #[\Override]
    public function getContext(): RequestContext
    {
        return $this->context;
    }
}
