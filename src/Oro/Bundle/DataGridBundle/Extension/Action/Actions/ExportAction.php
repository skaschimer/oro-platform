<?php

namespace Oro\Bundle\DataGridBundle\Extension\Action\Actions;

class ExportAction extends AbstractImportExportAction
{
    /**
     * @var array
     */
    protected $requiredOptions = [
        'exportProcessor',
    ];

    #[\Override]
    protected function getType()
    {
        return self::TYPE_EXPORT;
    }
}
