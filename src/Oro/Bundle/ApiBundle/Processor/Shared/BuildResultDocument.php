<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Request\DocumentBuilderInterface;
use Oro\Bundle\ApiBundle\Request\ErrorCompleterRegistry;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * The base class for processors responsible to build the response
 * using the response document builder from the context.
 */
abstract class BuildResultDocument implements ProcessorInterface
{
    protected ErrorCompleterRegistry $errorCompleterRegistry;
    protected LoggerInterface $logger;

    public function __construct(ErrorCompleterRegistry $errorCompleterRegistry, LoggerInterface $logger)
    {
        $this->errorCompleterRegistry = $errorCompleterRegistry;
        $this->logger = $logger;
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var Context $context */

        $documentBuilder = $context->getResponseDocumentBuilder();
        if (null === $documentBuilder) {
            return;
        }

        if ($context->hasErrors()) {
            try {
                $documentBuilder->setErrorCollection($context->getErrors());
                // remove errors from the context to avoid processing them by other processors
                $context->resetErrors();
            } catch (\Exception $e) {
                $this->processException($documentBuilder, $context, $e);
                $context->resetErrors();
            }
            $context->setResponseDocumentBuilder($documentBuilder);
            $context->removeResult();
        } elseif ($context->hasResult()) {
            $infoRecords = $context->getInfoRecords();
            if (null !== $infoRecords) {
                $documentBuilder->setMetadata($infoRecords);
            }
            try {
                $this->processResult($documentBuilder, $context);
            } catch (\Exception $e) {
                $this->processException($documentBuilder, $context, $e);
                $context->removeResult();
            }
            $context->setResponseDocumentBuilder($documentBuilder);
        }
    }

    protected function processException(
        DocumentBuilderInterface $documentBuilder,
        Context $context,
        \Exception $e
    ): void {
        $context->setResponseStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
        $error = Error::createByException($e);
        $this->errorCompleterRegistry->getErrorCompleter($context->getRequestType())
            ->complete($error, $context->getRequestType());
        $documentBuilder->clear();
        $documentBuilder->setErrorObject($error);

        $this->logger->error(
            'Building of the result document failed.',
            array_merge(['exception' => $e], $this->getExceptionLoggingContext($context))
        );
    }

    protected function getExceptionLoggingContext(Context $context): array
    {
        return [
            'action' => $context->getAction(),
            'entity' => $context->getClassName()
        ];
    }

    abstract protected function processResult(DocumentBuilderInterface $documentBuilder, Context $context): void;
}
