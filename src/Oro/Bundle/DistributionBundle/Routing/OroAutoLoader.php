<?php

namespace Oro\Bundle\DistributionBundle\Routing;

use Oro\Bundle\DistributionBundle\Event\RouteCollectionEvent;
use Oro\Component\Routing\Resolver\RouteOptionsResolverInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class OroAutoLoader extends AbstractLoader
{
    public function __construct(
        KernelInterface $kernel,
        RouteOptionsResolverInterface $routeOptionsResolver
    ) {
        parent::__construct(
            $kernel,
            $routeOptionsResolver,
            ['Resources/config/oro/routing.yml'],
            'oro_auto'
        );
    }

    #[\Override]
    public function load($file, $type = null)
    {
        $routes = parent::load($file, $type);

        return $this->dispatchEvent(RouteCollectionEvent::AUTOLOAD, $routes);
    }
}
