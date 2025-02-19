<?php

namespace Oro\Bundle\ApiBundle\Request\Rest;

use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandlerInterface;
use Oro\Bundle\ApiBundle\Filter\FilterValueAccessorInterface;
use Oro\Bundle\ApiBundle\Processor\ActionProcessorBagInterface;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Request\RequestActionHandler as BaseRequestActionHandler;
use Oro\Bundle\ApiBundle\Request\RestFilterValueAccessorFactory;
use Oro\Bundle\ApiBundle\Request\RestRequestHeaders;
use Oro\Component\ChainProcessor\AbstractParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Encoder\JsonEncode;

/**
 * Handles API actions for REST API.
 */
class RequestActionHandler extends BaseRequestActionHandler
{
    private ViewHandlerInterface $viewHandler;
    private RestFilterValueAccessorFactory $filterValueAccessorFactory;

    /**
     * @param string[]                       $requestType
     * @param ActionProcessorBagInterface    $actionProcessorBag
     * @param RestFilterValueAccessorFactory $filterValueAccessorFactory
     * @param ViewHandlerInterface           $viewHandler
     */
    public function __construct(
        array $requestType,
        ActionProcessorBagInterface $actionProcessorBag,
        RestFilterValueAccessorFactory $filterValueAccessorFactory,
        ViewHandlerInterface $viewHandler
    ) {
        parent::__construct($requestType, $actionProcessorBag);
        $this->filterValueAccessorFactory = $filterValueAccessorFactory;
        $this->viewHandler = $viewHandler;
    }

    #[\Override]
    protected function getRequestHeaders(Request $request): AbstractParameterBag
    {
        return new RestRequestHeaders($request);
    }

    #[\Override]
    protected function getRequestFilters(Request $request, string $action): FilterValueAccessorInterface
    {
        return $this->filterValueAccessorFactory->create($request, $action);
    }

    #[\Override]
    protected function prepareContext(Context $context, Request $request): void
    {
        parent::prepareContext($context, $request);
        if ($this->isCorsRequest($request)) {
            $context->setCorsRequest(true);
        }
    }

    #[\Override]
    protected function buildResponse(Context $context, Request $request): Response
    {
        $view = View::create($context->getResult());

        $view->setStatusCode($context->getResponseStatusCode() ?: Response::HTTP_OK);
        foreach ($context->getResponseHeaders()->toArray() as $key => $value) {
            $view->setHeader($key, $value);
        }

        // use custom handler because the response data are already normalized
        // and we do not need to additional processing of them
        $this->viewHandler->registerHandler(
            'json',
            function (ViewHandlerInterface $viewHandler, View $view, Request $request, $format) {
                $response = $view->getResponse();
                $data = $view->getData();
                if (null !== $data) {
                    // Allow json_encode to convert float to integer.
                    $encoder = new JsonEncode([JsonEncode::OPTIONS => 0]);
                    $response->setContent($encoder->encode($data, $format));
                } elseif (Response::HTTP_OK === $view->getStatusCode()) {
                    $response->headers->set('Content-Length', 0);
                }
                if (!$response->headers->has('Content-Type')) {
                    $response->headers->set('Content-Type', $request->getMimeType($format));
                }

                return $response;
            }
        );

        return $this->viewHandler->handle($view, $request);
    }

    private function isCorsRequest(Request $request): bool
    {
        return
            $request->headers->has(CorsHeaders::ORIGIN)
            && $request->headers->get(CorsHeaders::ORIGIN) !== $request->getSchemeAndHttpHost();
    }
}
