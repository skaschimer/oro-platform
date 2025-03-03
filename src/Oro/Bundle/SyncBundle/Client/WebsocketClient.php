<?php

namespace Oro\Bundle\SyncBundle\Client;

use Gos\Component\WebSocketClient\Exception\BadResponseException;
use Gos\Component\WebSocketClient\Exception\WebsocketException;
use Oro\Bundle\SyncBundle\Authentication\Ticket\TicketProviderInterface;
use Oro\Bundle\SyncBundle\Client\Wamp\Factory\WampClientFactoryInterface;
use Oro\Bundle\SyncBundle\Client\Wamp\WampClient;
use Oro\Bundle\SyncBundle\Exception\ValidationFailedException;
use Oro\Bundle\SyncBundle\Provider\WebsocketClientParametersProviderInterface;

/**
 * Websocket client with ticket-authentication.
 */
class WebsocketClient implements WebsocketClientInterface
{
    private WampClientFactoryInterface $wampClientFactory;

    private WebsocketClientParametersProviderInterface $clientParametersProvider;

    private TicketProviderInterface $ticketProvider;

    private ?WampClient $wampClient = null;

    public function __construct(
        WampClientFactoryInterface $wampClientFactory,
        WebsocketClientParametersProviderInterface $clientParametersProvider,
        TicketProviderInterface $ticketProvider
    ) {
        $this->wampClientFactory = $wampClientFactory;
        $this->clientParametersProvider = $clientParametersProvider;
        $this->ticketProvider = $ticketProvider;
    }

    /**
     *
     * @throws WebsocketException
     * @throws BadResponseException
     */
    #[\Override]
    public function connect(): ?string
    {
        $urlInfo = parse_url($this->clientParametersProvider->getPath()) + ['path' => '', 'query' => ''];
        parse_str($urlInfo['query'], $query);
        $query['ticket'] = $this->ticketProvider->generateTicket();
        $pathWithTicket = sprintf('%s?%s', $urlInfo['path'], http_build_query($query));

        return $this->getWampClient()->connect($pathWithTicket);
    }

    #[\Override]
    public function disconnect(): bool
    {
        return $this->getWampClient()->disconnect();
    }

    #[\Override]
    public function isConnected(): bool
    {
        return $this->getWampClient()->isConnected();
    }

    /**
     *
     * @throws WebsocketException
     * @throws BadResponseException
     * @throws ValidationFailedException
     */
    #[\Override]
    public function publish(string $topicUri, $payload, array $exclude = [], array $eligible = []): bool
    {
        $this->validatePayload($payload);
        $this->ensureClientConnected();

        $this->getWampClient()->publish($topicUri, json_encode($payload), $exclude, $eligible);

        return true;
    }

    /**
     *
     * @throws WebsocketException
     * @throws BadResponseException
     */
    #[\Override]
    public function prefix(string $prefix, string $uri): bool
    {
        $this->ensureClientConnected();
        $this->getWampClient()->prefix($prefix, $uri);

        return true;
    }

    /**
     *
     * @throws WebsocketException
     * @throws BadResponseException
     */
    #[\Override]
    public function call(string $procUri, array $arguments = []): bool
    {
        $this->ensureClientConnected();
        $this->getWampClient()->call($procUri, $arguments);

        return true;
    }

    /**
     *
     * @throws WebsocketException
     * @throws BadResponseException
     * @throws ValidationFailedException
     */
    #[\Override]
    public function event(string $topicUri, $payload): bool
    {
        $this->validatePayload($payload);
        $this->ensureClientConnected();

        $this->getWampClient()->event($topicUri, json_encode($payload));

        return true;
    }

    private function getWampClient(): WampClient
    {
        if ($this->wampClient === null) {
            $this->wampClient = $this->wampClientFactory->createClient($this->clientParametersProvider);
        }

        return $this->wampClient;
    }

    /**
     * @throws BadResponseException
     * @throws WebsocketException
     */
    private function ensureClientConnected(): void
    {
        if (!$this->isConnected()) {
            $this->connect();
        }
    }

    /**
     * @param mixed $payload
     *
     * @throws ValidationFailedException
     */
    private function validatePayload($payload): void
    {
        $encodedJson = json_encode($payload);
        if ($encodedJson === false && json_last_error() !== JSON_ERROR_NONE) {
            throw new ValidationFailedException(json_last_error_msg());
        }
    }
}
