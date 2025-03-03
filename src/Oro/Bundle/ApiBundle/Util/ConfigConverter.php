<?php

namespace Oro\Bundle\ApiBundle\Util;

use Oro\Bundle\ApiBundle\Provider\EntityOverrideProviderRegistry;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Component\EntitySerializer\AssociationQuery;
use Oro\Component\EntitySerializer\ConfigConverter as BaseConfigConverter;
use Oro\Component\EntitySerializer\EntityConfig;
use Oro\Component\EntitySerializer\FieldConfig;

/**
 * Provides a method to convert normalized configuration of the EntityConfig object.
 */
class ConfigConverter extends BaseConfigConverter
{
    private EntityOverrideProviderRegistry $entityOverrideProviderRegistry;
    private ?RequestType $requestType = null;

    public function __construct(EntityOverrideProviderRegistry $entityOverrideProviderRegistry)
    {
        $this->entityOverrideProviderRegistry = $entityOverrideProviderRegistry;
    }

    public function setRequestType(?RequestType $requestType): void
    {
        $this->requestType = $requestType;
    }

    #[\Override]
    protected function buildEntityConfig(EntityConfig $result, array $config): void
    {
        parent::buildEntityConfig($result, $config);

        if (!empty($config[ConfigUtil::RESOURCE_CLASS])) {
            $result->set(ConfigUtil::RESOURCE_CLASS, $config[ConfigUtil::RESOURCE_CLASS]);
        }
        if (($config[AclProtectedQueryResolver::SKIP_ACL_FOR_ROOT_ENTITY] ?? false)) {
            $result->set(AclProtectedQueryResolver::SKIP_ACL_FOR_ROOT_ENTITY, true);
        }
    }

    #[\Override]
    protected function setAssociationQuery(FieldConfig $result, array $config): void
    {
        if (isset($config[ConfigUtil::ASSOCIATION_QUERY])) {
            $result->set(
                ConfigUtil::ASSOCIATION_QUERY,
                new AssociationQuery(
                    $config[ConfigUtil::ASSOCIATION_QUERY],
                    $this->getEntityClass($config[ConfigUtil::TARGET_CLASS]),
                    $this->isCollection($config)
                )
            );
        }
    }

    protected function getEntityClass(string $class): string
    {
        if (null === $this->requestType) {
            return $class;
        }

        if (ExtendHelper::isOutdatedEnumOptionEntity($class)) {
            return EnumOption::class;
        }

        $entityClass = $this->entityOverrideProviderRegistry
            ->getEntityOverrideProvider($this->requestType)
            ->getEntityClass($class);
        if ($entityClass) {
            return $entityClass;
        }

        return $class;
    }

    private function isCollection(array $config): bool
    {
        return
            !isset($config[ConfigUtil::TARGET_TYPE])
            || ConfigUtil::TO_ONE !== $config[ConfigUtil::TARGET_TYPE];
    }
}
