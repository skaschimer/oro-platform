<?php

namespace Oro\Bundle\MessageQueueBundle\Profiler;

use Oro\Component\MessageQueue\Client\MessagePriority;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Client\TraceableMessageProducer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;

/**
 * Collects statistic about sent messages
 */
class MessageQueueCollector extends DataCollector
{
    /**
     * @var MessageProducerInterface
     */
    private $messageProducer;

    public function __construct(MessageProducerInterface $messageProducer)
    {
        $this->messageProducer = $messageProducer;
        $this->reset();
    }

    #[\Override]
    public function collect(Request $request, Response $response, ?\Throwable $exception = null)
    {
        if ($this->messageProducer instanceof TraceableMessageProducer) {
            $this->data['sent_messages'] = $this->messageProducer->getTraces();
        }
    }

    /**
     * @return array
     */
    public function getSentMessages()
    {
        return $this->data['sent_messages'];
    }

    /**
     * @param string $priority
     *
     * @return string
     */
    public function prettyPrintPriority($priority)
    {
        $map = [
            MessagePriority::VERY_LOW => 'very low',
            MessagePriority::LOW => 'low',
            MessagePriority::NORMAL => 'normal',
            MessagePriority::HIGH => 'high',
            MessagePriority::VERY_HIGH => 'very high',
        ];

        return isset($map[$priority]) ? $map[$priority] : $priority;
    }

    /**
     * @param string $message
     *
     * @return string
     */
    public function prettyPrintMessage($message)
    {
        if (is_scalar($message)) {
            return htmlspecialchars($message);
        }

        return htmlspecialchars(
            json_encode($message, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        );
    }

    #[\Override]
    public function getName(): string
    {
        return 'message_queue';
    }

    #[\Override]
    public function reset()
    {
        $this->data = [
            'sent_messages' => [],
        ];
    }
}
