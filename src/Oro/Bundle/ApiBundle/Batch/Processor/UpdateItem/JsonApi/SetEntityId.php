<?php

namespace Oro\Bundle\ApiBundle\Batch\Processor\UpdateItem\JsonApi;

use Oro\Bundle\ApiBundle\Batch\Processor\UpdateItem\BatchUpdateItemContext;
use Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilder as JsonApiDoc;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Extracts the entity identifier from the request data and adds it to the context.
 */
class SetEntityId implements ProcessorInterface
{
    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var BatchUpdateItemContext $context */

        $requestData = $context->getRequestData();
        if (\is_array($requestData) && \array_key_exists(JsonApiDoc::DATA, $requestData)) {
            $data = $requestData[JsonApiDoc::DATA];
            if (\is_array($data) && \array_key_exists(JsonApiDoc::ID, $data)) {
                $context->setId($data[JsonApiDoc::ID]);
            }
        }
    }
}
