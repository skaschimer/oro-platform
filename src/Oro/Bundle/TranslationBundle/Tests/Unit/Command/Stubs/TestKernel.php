<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Command\Stubs;

use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpKernel\Kernel;

/**
 * Kernel stub class
 */
class TestKernel extends Kernel
{
    /** @var string */
    private $projectDir;

    public function __construct(string $projectDir)
    {
        $this->projectDir = $projectDir;
        parent::__construct('test_stub_env', true);
    }

    #[\Override]
    public function getProjectDir(): string
    {
        return $this->projectDir;
    }

    public function init()
    {
    }

    #[\Override]
    public function registerBundles(): iterable
    {
        return [];
    }

    #[\Override]
    public function registerContainerConfiguration(LoaderInterface $loader)
    {
    }
}
