<?php

namespace Oro\Bundle\EntityConfigBundle\Provider;

use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Config\ConfigModelManager;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;

class DeletedAttributeProvider implements DeletedAttributeProviderInterface
{
    /**
     * @var ConfigModelManager
     */
    protected $configModelManager;

    /**
     * @var AttributeValueProviderInterface
     */
    protected $attributeValueProvider;

    public function __construct(
        ConfigModelManager $configModelManager,
        AttributeValueProviderInterface $attributeValueProvider
    ) {
        $this->configModelManager = $configModelManager;
        $this->attributeValueProvider = $attributeValueProvider;
    }

    /**
     * @param array $ids
     * @return FieldConfigModel[]
     */
    #[\Override]
    public function getAttributesByIds(array $ids)
    {
        if (!$this->configModelManager->checkDatabase()) {
            return [];
        }

        $repository = $this->configModelManager->getEntityManager()->getRepository(FieldConfigModel::class);

        return $repository->getAttributesByIds($ids);
    }

    #[\Override]
    public function removeAttributeValues(AttributeFamily $attributeFamily, array $names)
    {
        if (!$names) {
            return;
        }

        $this->attributeValueProvider->removeAttributeValues($attributeFamily, $names);
    }
}
