<?php

namespace Oro\Bundle\WorkflowBundle\Configuration\Import;

use Symfony\Component\Config\FileLocatorInterface;

/**
 * FileLocator uses the BaseFileLocator to locate resources in bundles.
 * Indicates the bundle folder "Resources/config/oro/" in the case when ":" is present in path name.
 */
class ResourceFileLocator implements FileLocatorInterface
{
    /** @var FileLocatorInterface */
    private $fileLocator;

    public function __construct(FileLocatorInterface $fileLocator)
    {
        $this->fileLocator = $fileLocator;
    }

    #[\Override]
    public function locate($name, $currentPath = null, $first = true): string|array
    {
        $name = str_replace(':', '/Resources/config/oro/', $name);

        return $this->fileLocator->locate($name, $currentPath, $first);
    }
}
