<?php

namespace Oro\Bundle\DashboardBundle\Provider\Converters;

use Oro\Bundle\DashboardBundle\Provider\ConfigValueConverterAbstract;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Returns string representation of entity fields converted value
 */
class WidgetSortByConverter extends ConfigValueConverterAbstract
{
    /** @var ConfigProvider */
    protected $entityConfigProvider;

    /** @var TranslatorInterface */
    protected $translator;

    /** @var array */
    protected $orderToLabelMap = [
        'ASC' => 'oro.dashboard.widget.sort_by.order.asc.label',
        'DESC' => 'oro.dashboard.widget.sort_by.order.desc.label',
    ];

    public function __construct(ConfigProvider $entityConfigProvider, TranslatorInterface $translator)
    {
        $this->entityConfigProvider = $entityConfigProvider;
        $this->translator = $translator;
    }

    #[\Override]
    public function getViewValue($value)
    {
        if (empty($value['property']) ||
            !$this->entityConfigProvider->hasConfig($value['className'], $value['property'])
        ) {
            return null;
        }

        return sprintf(
            '%s %s',
            $this->translator->trans(
                (string) $this->entityConfigProvider
                    ->getConfig($value['className'], $value['property'])
                    ->get('label')
            ),
            $this->translator->trans($this->orderToLabelMap[$value['order']])
        );
    }
}
