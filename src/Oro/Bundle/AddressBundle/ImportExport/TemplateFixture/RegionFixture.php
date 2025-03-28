<?php

namespace Oro\Bundle\AddressBundle\ImportExport\TemplateFixture;

use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\ImportExportBundle\TemplateFixture\AbstractTemplateRepository;

class RegionFixture extends AbstractTemplateRepository
{
    #[\Override]
    public function getEntityClass()
    {
        return 'Oro\Bundle\AddressBundle\Entity\Region';
    }

    #[\Override]
    protected function createEntity($key)
    {
        $result = new Region('US-' . $key);

        return $result;
    }

    /**
     * @param string $key
     * @param Region $entity
     */
    #[\Override]
    public function fillEntityData($key, $entity)
    {
        $countryRepo = $this->templateManager
            ->getEntityRepository('Oro\Bundle\AddressBundle\Entity\Country');

        switch ($key) {
            case 'NY':
                $entity->setCode($key);
                $country = $countryRepo->getEntity('US');
                $country->addRegion($entity);
                return;
        }

        parent::fillEntityData($key, $entity);
    }
}
