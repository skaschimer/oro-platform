<?php

namespace Oro\Bundle\PlatformBundle\Tests\Functional\DependencyInjection\Compiler\Stub;

use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class TestServiceLocatorInjection implements
    TestServiceLocatorInjectionInterface,
    ServiceSubscriberInterface
{
    /** @var ContainerInterface */
    protected $container;

    public function __construct(?ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    public function setContainer(?ContainerInterface $container = null): void
    {
        $this->container = $container;
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return [
            'translator' => TranslatorInterface::class,
            TranslatorInterface::class
        ];
    }

    #[\Override]
    public function getContainer(): ContainerInterface
    {
        return $this->container;
    }

    #[\Override]
    public function validateInjectedServiceLocator(): void
    {
        foreach (self::getSubscribedServices() as $alias => $type) {
            $id = $type;
            if (!is_numeric($alias)) {
                $id = $alias;
            }
            if (!$this->container->has($id)) {
                throw new \LogicException(sprintf(
                    'The "%s" service does not exist in the service locator.',
                    $id
                ));
            }
            $service = $this->container->get($id);
            if ('?' !== $type && !is_a($service, $type)) {
                throw new \LogicException(sprintf(
                    'The "%s" service should be instance of "%s", got "%s".',
                    $id,
                    $type,
                    get_class($service)
                ));
            }
        }
    }
}
