<?php

namespace Oro\Bundle\EmailBundle\Datagrid\Extension\MassAction\Actions;

use Oro\Bundle\DataGridBundle\Extension\Action\ActionConfiguration;
use Oro\Bundle\DataGridBundle\Extension\MassAction\Actions\AbstractMassAction;
use Oro\Bundle\EmailBundle\Datagrid\Extension\MassAction\MarkMassActionHandler;
use Symfony\Component\HttpFoundation\Request;

class MarkReadMassAction extends AbstractMassAction
{
    /** @var array */
    protected $requiredOptions = ['handler', 'entity_name', 'data_identifier'];

    #[\Override]
    public function setOptions(ActionConfiguration $options)
    {
        if (empty($options['handler'])) {
            $options['handler'] = 'oro_email.mass_action.mark_handler';
        }

        if (empty($options['frontend_type'])) {
            $options['frontend_type'] = 'mark-email-mass';
        }

        if (empty($options['route'])) {
            $options['route'] = 'oro_email_mark_massaction';
        }

        if (empty($options['route_parameters'])) {
            $options['route_parameters'] = [];
        }

        if (empty($options['frontend_handle'])) {
            $options['frontend_handle'] = 'ajax';
        }

        $options['mark_type'] = MarkMassActionHandler::MARK_READ;
        $options['confirmation'] = false;

        return parent::setOptions($options);
    }

    #[\Override]
    protected function getAllowedRequestTypes()
    {
        return [Request::METHOD_POST];
    }

    #[\Override]
    protected function getRequestType()
    {
        return Request::METHOD_POST;
    }
}
