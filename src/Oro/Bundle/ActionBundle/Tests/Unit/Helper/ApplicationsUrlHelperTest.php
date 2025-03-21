<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Helper;

use Oro\Bundle\ActionBundle\Helper\ApplicationsUrlHelper;
use Oro\Bundle\ActionBundle\Provider\RouteProviderInterface;
use Symfony\Component\Routing\RouterInterface;

class ApplicationsUrlHelperTest extends \PHPUnit\Framework\TestCase
{
    /** @var RouteProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $routerProvider;

    /** @var RouterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $router;

    /** @var ApplicationsUrlHelper */
    private $instance;

    #[\Override]
    protected function setUp(): void
    {
        $this->routerProvider = $this->createMock(RouteProviderInterface::class);
        $this->router = $this->createMock(RouterInterface::class);

        $this->instance = new ApplicationsUrlHelper($this->routerProvider, $this->router);
    }

    public function testGetExecutionUrl()
    {
        $parameters = ['param1' => 'val1'];

        $this->routerProvider->expects($this->once())
            ->method('getExecutionRoute')
            ->willReturn('extension_route');

        $this->router->expects($this->once())
            ->method('generate')
            ->with('extension_route', $parameters)
            ->willReturn('ok_extension');

        $this->assertEquals('ok_extension', $this->instance->getExecutionUrl($parameters));
    }

    public function testGetDialogUrl()
    {
        $parameters = ['param1' => 'val1'];

        $this->routerProvider->expects($this->once())
            ->method('getFormDialogRoute')
            ->willReturn('dialog_route');

        $this->router->expects($this->once())
            ->method('generate')
            ->with('dialog_route', $parameters)
            ->willReturn('ok_dialog');

        $this->assertEquals('ok_dialog', $this->instance->getDialogUrl($parameters));
    }

    public function testGetPageUrl()
    {
        $parameters = ['param1' => 'val1'];

        $this->routerProvider->expects($this->once())
            ->method('getFormPAgeRoute')
            ->willReturn('page_route');

        $this->router->expects($this->once())
            ->method('generate')
            ->with('page_route', $parameters)
            ->willReturn('ok_dialog');

        $this->assertEquals('ok_dialog', $this->instance->getPageUrl($parameters));
    }
}
