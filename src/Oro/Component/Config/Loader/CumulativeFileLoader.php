<?php

namespace Oro\Component\Config\Loader;

use Oro\Component\Config\CumulativeResource;
use Oro\Component\Config\CumulativeResourceInfo;
use Oro\Component\Config\CumulativeResourceManager;

/**
 * The base class for classes responsible to load resources from different kind of files, e.g. from YAML files.
 */
abstract class CumulativeFileLoader implements CumulativeResourceLoader
{
    /** @var string */
    protected $relativeFilePath;

    /**
     * @var string
     *
     * not serializable. it sets in setRelativeFilePath method
     */
    protected $resource;

    /**
     * @var string
     *
     * not serializable. it sets in setRelativeFilePath method
     */
    protected $resourceName;

    /**
     * @param string $relativeFilePath The relative path to a resource file starts from bundle folder
     */
    public function __construct($relativeFilePath)
    {
        $this->setRelativeFilePath($relativeFilePath);
    }

    /**
     * Gets relative path to a resource file
     *
     * @return string
     */
    public function getRelativeFilePath()
    {
        return $this->relativeFilePath;
    }

    /**
     * Sets relative path to a resource file
     *
     * @param string $relativeFilePath The relative path to a resource file starts from bundle folder
     */
    public function setRelativeFilePath($relativeFilePath)
    {
        $relativeFilePath = str_replace('\\', '/', $relativeFilePath);
        $delim = strrpos($relativeFilePath, '/');
        $this->resourceName = pathinfo(
            false === $delim ? $relativeFilePath : substr($relativeFilePath, $delim + 1),
            PATHINFO_FILENAME
        );
        $path = DIRECTORY_SEPARATOR === '/'
            ? $relativeFilePath
            : str_replace('/', DIRECTORY_SEPARATOR, $relativeFilePath);
        if (str_starts_with($relativeFilePath, '/')) {
            $this->resource = substr($relativeFilePath, 1);
            $this->relativeFilePath = $path;
        } else {
            $this->resource = $relativeFilePath;
            $this->relativeFilePath = DIRECTORY_SEPARATOR . $path;
        }
    }

    #[\Override]
    public function getResource()
    {
        return $this->resource;
    }

    #[\Override]
    public function load($bundleClass, $bundleDir, $bundleAppDir = '', $folderPlaceholder = '')
    {
        $realPath = $this->getResourcePath($bundleAppDir, $bundleDir);

        if (!$realPath) {
            return null;
        }

        return new CumulativeResourceInfo(
            $bundleClass,
            $this->resourceName,
            $realPath,
            $this->doLoad($realPath),
            $folderPlaceholder
        );
    }

    /**
     * @param string $realPath
     *
     * @return array
     */
    protected function doLoad($realPath)
    {
        $data = $this->loadFile($realPath);

        if (!is_array($data)) {
            return [];
        }

        return (array)$data;
    }

    /**
     * Returns realpath for source file if file exists or null if file does not exist.
     * Priority loading remains for the $bundleAppDir.
     *
     * @param string $bundleAppDir The bundle directory inside the application resources directory
     * @param string $bundleDir    The bundle root directory
     *
     * @return string|null
     */
    public function getResourcePath($bundleAppDir, $bundleDir)
    {
        $path = $this->getBundleAppResourcePath($bundleAppDir);
        if (!$path) {
            $path = $this->getBundleResourcePath($bundleDir);
        }

        return $path;
    }

    /**
     * Returns realpath for source file in the $bundleAppDir directory if file exists
     * or null if file does not exist.
     *
     * @param string $bundleAppDir
     *
     * @return string|null
     */
    protected function getBundleAppResourcePath($bundleAppDir)
    {
        if (CumulativeResourceManager::getInstance()->isDir($bundleAppDir)) {
            $path = $this->normalizeBundleAppDir($bundleAppDir);
            if (is_file($path)) {
                return realpath($path);
            }
        }

        return null;
    }

    /**
     * Returns realpath for source file in the <Bundle> Resources directory if file exists
     * or null if file does not exist.
     *
     * @param string $bundleDir The bundle root directory
     *
     * @return string|null
     */
    protected function getBundleResourcePath($bundleDir)
    {
        $path = $bundleDir . $this->relativeFilePath;

        return is_file($path)
            ? realpath($path)
            : null;
    }

    #[\Override]
    public function registerFoundResource($bundleClass, $bundleDir, $bundleAppDir, CumulativeResource $resource)
    {
        $path = $this->getBundleAppResourcePath($bundleAppDir);
        if ($path) {
            $resource->addFound($bundleClass, $path);
        } else {
            $path = $this->getBundleResourcePath($bundleDir);
            if ($path) {
                $resource->addFound($bundleClass, $path);
            }
        }
    }

    #[\Override]
    public function isResourceFresh($bundleClass, $bundleDir, $bundleAppDir, CumulativeResource $resource, $timestamp)
    {
        if (CumulativeResourceManager::getInstance()->isDir($bundleAppDir)) {
            $path = $this->normalizeBundleAppDir($bundleAppDir);
            if ($resource->isFound($bundleClass, $path)) {
                // check existing and removed resource
                return $this->isFileFresh($path, $timestamp);
            }
            // check new resource
            if (is_file($path)) {
                return false;
            }
        }

        $path = $bundleDir . $this->relativeFilePath;
        if ($resource->isFound($bundleClass, $path)) {
            // check existing and removed resource
            return $this->isFileFresh($path, $timestamp);
        }

        // check new resource
        return !is_file($path);
    }

    public function __serialize(): array
    {
        return [$this->relativeFilePath, $this->resource, $this->resourceName];
    }

    public function __unserialize(array $serialized): void
    {
        $this->relativeFilePath = $serialized[0];
        if (isset($serialized[1])) {
            $this->resource = $serialized[1];
        }
        if (isset($serialized[2])) {
            $this->resourceName = $serialized[2];
        }
    }

    /**
     * Loads a file
     *
     * @param string $file A real path to a file
     *
     * @return array|null
     */
    abstract protected function loadFile($file);

    /**
     * @param string $bundleAppDir
     *
     * @return string
     */
    protected function normalizeBundleAppDir($bundleAppDir)
    {
        return $bundleAppDir . str_replace('/Resources', '', $this->relativeFilePath);
    }

    /**
     * @param string $filename
     * @param int    $timestamp
     *
     * @return boolean
     */
    private function isFileFresh($filename, $timestamp)
    {
        $filemtime = @filemtime($filename);

        return false !== $filemtime && $filemtime < $timestamp;
    }
}
