<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource\Shared;

use Oro\Bundle\ApiBundle\Processor\Subresource\SubresourceContext;
use Oro\Bundle\ApiBundle\Request\DataType;

/**
 * Loads extended association data using the EntitySerializer component.
 * As returned data is already normalized, the "normalize_data" group will be skipped.
 */
class LoadExtendedAssociation extends LoadCustomAssociation
{
    #[\Override]
    protected function isSupportedAssociation(string $dataType): bool
    {
        return DataType::isExtendedAssociation($dataType);
    }

    #[\Override]
    protected function loadAssociationData(
        SubresourceContext $context,
        string $associationName,
        string $dataType
    ): void {
        [$associationType,] = DataType::parseExtendedAssociation($dataType);
        $this->saveAssociationDataToContext(
            $context,
            $this->loadData($context, $associationName, $this->isCollection($associationType))
        );
    }
}
