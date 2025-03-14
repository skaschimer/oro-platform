<?php

namespace Oro\Bundle\DataGridBundle\Extension\MassAction\Actions;

use Oro\Bundle\DataGridBundle\Extension\Action\ActionConfiguration;
use Oro\Bundle\DataGridBundle\Extension\Action\Actions\AbstractAction;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionExtension;
use Symfony\Component\HttpFoundation\Request;

/**
 * Base class for datagrid mass actions.
 */
class AbstractMassAction extends AbstractAction implements MassActionInterface
{
    #[\Override]
    public function setOptions(ActionConfiguration $options)
    {
        if (empty($options['frontend_type'])) {
            $options['frontend_type'] = 'mass';
        }

        if (!empty($options['icon'])) {
            $icon = $options['icon'];
            if (empty($options['frontend'])) {
                $icon = 'fa-' . $icon;
            }

            $options['launcherOptions'] = [
                'iconClassName' => $icon,
            ];
            unset($options['icon']);
        }

        if (empty($options[MassActionExtension::ALLOWED_REQUEST_TYPES])) {
            $options[MassActionExtension::ALLOWED_REQUEST_TYPES] = $this->getAllowedRequestTypes();
        }

        if (empty($options['requestType'])) {
            $options['requestType'] = $this->getRequestType();
        }

        return parent::setOptions($options);
    }

    /**
     * @return array
     */
    protected function getAllowedRequestTypes()
    {
        return [Request::METHOD_GET];
    }

    /**
     * @return string
     */
    protected function getRequestType()
    {
        return Request::METHOD_GET;
    }
}
