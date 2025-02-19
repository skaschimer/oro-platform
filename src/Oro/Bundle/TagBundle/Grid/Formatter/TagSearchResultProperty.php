<?php

namespace Oro\Bundle\TagBundle\Grid\Formatter;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\SearchBundle\Datagrid\Extension\SearchResultProperty;
use Symfony\Component\Security\Acl\Util\ClassUtils;
use Twig\Environment;

/**
 * Property formatter for tags for datagrid
 */
class TagSearchResultProperty extends SearchResultProperty
{
    /** @var ConfigProvider */
    protected $entityConfigProvider;

    /** @var string */
    private $defaultTemplate;

    /**
     * @param Environment       $environment
     * @param ConfigProvider    $configProvider
     * @param string            $defaultTemplate
     */
    public function __construct(Environment $environment, ConfigProvider $configProvider, $defaultTemplate)
    {
        parent::__construct($environment);

        $this->entityConfigProvider = $configProvider;
        $this->defaultTemplate      = $defaultTemplate;
    }

    #[\Override]
    public function getValue(ResultRecordInterface $record)
    {
        $entity      = $record->getValue('entity');
        $entityClass = ClassUtils::getRealClass($entity);
        if ($this->mappingProvider->isClassSupported($entityClass)) {
            return parent::getValue($record);
        }

        $this->params[self::TEMPLATE_KEY] = $this->defaultTemplate;

        return $this->environment->render(
            $this->get(self::TEMPLATE_KEY),
            [
                'entityType'   => $this->entityConfigProvider->getConfig($entityClass)->get('label'),
                'entity'       => $entity,
                'indexer_item' => $record->getValue('indexer_item')
            ]
        );
    }
}
