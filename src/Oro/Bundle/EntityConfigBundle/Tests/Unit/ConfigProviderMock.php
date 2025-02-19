<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Exception\RuntimeException;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityConfigBundle\Provider\PropertyConfigBag;

/**
 * Special ConfigProvider mock used for testing purposes.
 */
class ConfigProviderMock extends ConfigProvider
{
    /** @var Config[] */
    private $entityConfigs = [];

    /** @var array of Config[] */
    private $fieldConfigs = [];

    /** @var bool[] */
    private $hiddenEntities = [];

    /** @var array of bool[] */
    private $hiddenFields = [];

    /**
     * @param ConfigManager $configManager
     * @param string        $scope
     * @param array         $config
     */
    public function __construct(ConfigManager $configManager, $scope, array $config = [])
    {
        parent::__construct($configManager, $scope, new PropertyConfigBag([$scope => $config]));
    }

    /**
     * @param string $className
     * @param array  $values
     * @param bool   $hidden
     *
     * @return Config
     */
    public function addEntityConfig($className, $values = [], $hidden = false)
    {
        if (empty($className)) {
            throw new \InvalidArgumentException('$className must not be empty.');
        }

        $config = new Config(new EntityConfigId($this->getScope(), $className));
        $config->setValues($values);

        $this->entityConfigs[$className] = $config;
        if ($hidden) {
            $this->hiddenEntities[$className] = true;
        } else {
            unset($this->hiddenEntities[$className]);
        }

        return $config;
    }

    /**
     * @param string $className
     * @param string $fieldName
     * @param string $fieldType
     * @param array  $values
     * @param bool   $hidden
     *
     * @return Config
     */
    public function addFieldConfig($className, $fieldName, $fieldType = null, $values = [], $hidden = false)
    {
        if (empty($className)) {
            throw new \InvalidArgumentException('$className must not be empty.');
        }
        if (empty($fieldName)) {
            throw new \InvalidArgumentException('$fieldName must not be empty.');
        }

        $config = new Config(new FieldConfigId($this->getScope(), $className, $fieldName, $fieldType));
        $config->setValues($values);

        $this->fieldConfigs[$className][$fieldName] = $config;
        if ($hidden) {
            $this->hiddenFields[$className][$fieldName] = true;
        } else {
            unset($this->hiddenFields[$className][$fieldName]);
        }

        return $config;
    }

    #[\Override]
    public function getId(
        ?string $className = null,
        ?string $fieldName = null,
        ?string $fieldType = null
    ): ConfigIdInterface {
        return $fieldName
            ? new FieldConfigId($this->getScope(), $className, $fieldName, $fieldType)
            : new EntityConfigId($this->getScope(), $className);
    }

    #[\Override]
    public function hasConfig(string $className, ?string $fieldName = null): bool
    {
        return $fieldName
            ? isset($this->fieldConfigs[$className][$fieldName])
            : isset($this->entityConfigs[$className]);
    }

    #[\Override]
    public function hasConfigById(ConfigIdInterface $configId): bool
    {
        return $configId instanceof FieldConfigId
            ? $this->hasConfig($configId->getClassName(), $configId->getFieldName())
            : $this->hasConfig($configId->getClassName());
    }

    #[\Override]
    public function getConfig(?string $className = null, ?string $fieldName = null): ConfigInterface
    {
        if (!$this->hasConfig($className, $fieldName)) {
            if ($fieldName) {
                throw new RuntimeException(sprintf(
                    'A config for the field "%s::%s" does not exist. Scope: %s.',
                    $className,
                    $fieldName,
                    $this->getScope()
                ));
            }
            throw new RuntimeException(sprintf(
                'A config for the entity "%s" does not exist. Scope: %s.',
                $className,
                $this->getScope()
            ));
        }

        return $fieldName
            ? $this->fieldConfigs[$className][$fieldName]
            : $this->entityConfigs[$className];
    }

    #[\Override]
    public function getConfigById(ConfigIdInterface $configId): ConfigInterface
    {
        return $configId instanceof FieldConfigId
            ? $this->getConfig($configId->getClassName(), $configId->getFieldName())
            : $this->getConfig($configId->getClassName());
    }

    #[\Override]
    public function getIds(?string $className = null, bool $withHidden = false): array
    {
        $result = [];
        if ($className) {
            /** @var Config $config */
            $fieldConfigs = $this->fieldConfigs[$className] ?? [];
            foreach ($fieldConfigs as $config) {
                if (!$withHidden) {
                    /** @var FieldConfigId $fieldId */
                    $fieldId = $config->getId();
                    if (isset($this->hiddenFields[$fieldId->getClassName()][$fieldId->getFieldName()])) {
                        continue;
                    }
                }
                $result[] = $config->getId();
            }
        } else {
            foreach ($this->entityConfigs as $config) {
                if (!$withHidden) {
                    /** @var EntityConfigId $fieldId */
                    $entityId = $config->getId();
                    if (isset($this->hiddenEntities[$entityId->getClassName()])) {
                        continue;
                    }
                }
                $result[] = $config->getId();
            }
        }

        return $result;
    }

    #[\Override]
    public function getConfigs(?string $className = null, bool $withHidden = false): array
    {
        $result = [];
        if ($className) {
            /** @var Config $config */
            $fieldConfigs = $this->fieldConfigs[$className] ?? [];
            foreach ($fieldConfigs as $config) {
                if (!$withHidden) {
                    /** @var FieldConfigId $fieldId */
                    $fieldId = $config->getId();
                    if (isset($this->hiddenFields[$fieldId->getClassName()][$fieldId->getFieldName()])) {
                        continue;
                    }
                }
                $result[] = $config;
            }
        } else {
            foreach ($this->entityConfigs as $config) {
                if (!$withHidden) {
                    /** @var EntityConfigId $fieldId */
                    $entityId = $config->getId();
                    if (isset($this->hiddenEntities[$entityId->getClassName()])) {
                        continue;
                    }
                }
                $result[] = $config;
            }
        }

        return $result;
    }
}
