<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Type;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\FormBundle\Autocomplete\SearchRegistry;
use Oro\Bundle\FormBundle\Form\Type\OroJquerySelect2HiddenType;
use Oro\Bundle\FormBundle\Form\Type\Select2Type;
use Symfony\Component\Form\AbstractExtension;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

class EntitySelectOrCreateInlineFormExtension extends AbstractExtension
{
    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var SearchRegistry
     */
    protected $searchRegistry;

    /**
     * @var ConfigProvider
     */
    protected $configProvider;

    public function __construct(
        EntityManager $em,
        SearchRegistry $searchRegistry,
        ConfigProvider $configProvider
    ) {
        $this->em             = $em;
        $this->searchRegistry = $searchRegistry;
        $this->configProvider = $configProvider;
    }

    /**
     * @return array|\Symfony\Component\Form\FormTypeInterface[]
     */
    #[\Override]
    protected function loadTypes(): array
    {
        return [
            new OroJquerySelect2HiddenType($this->em, $this->searchRegistry, $this->configProvider),
            new Select2Type(
                HiddenType::class,
                'oro_select2_hidden'
            )
        ];
    }
}
