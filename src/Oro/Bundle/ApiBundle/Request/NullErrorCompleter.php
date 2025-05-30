<?php

namespace Oro\Bundle\ApiBundle\Request;

use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Model\Error;

/**
 * The error completer that keeps all properties of Error objects as is.
 */
class NullErrorCompleter implements ErrorCompleterInterface
{
    #[\Override]
    public function complete(Error $error, RequestType $requestType, ?EntityMetadata $metadata = null): void
    {
    }

    #[\Override]
    public function fixIncludedEntityPath(
        string $entityPath,
        Error $error,
        RequestType $requestType,
        ?EntityMetadata $metadata = null
    ): void {
    }
}
