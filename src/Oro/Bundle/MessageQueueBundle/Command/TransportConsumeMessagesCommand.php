<?php

declare(strict_types=1);

namespace Oro\Bundle\MessageQueueBundle\Command;

use Oro\Bundle\MessageQueueBundle\Consumption\Extension\ChainExtension;
use Oro\Component\MessageQueue\Consumption\ConsumeMessagesCommand;
use Oro\Component\MessageQueue\Consumption\Extension\LoggerExtension;
use Oro\Component\MessageQueue\Consumption\ExtensionInterface;
use Oro\Component\MessageQueue\Consumption\QueueConsumer;
use Oro\Component\MessageQueue\Log\ConsumerState;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Processes a message-queue with a specific processor.
 */
#[AsCommand(name: 'oro:message-queue:transport:consume')]
class TransportConsumeMessagesCommand extends ConsumeMessagesCommand
{
    private ConsumerState $consumerState;

    private LoggerInterface $logger;

    public function __construct(
        QueueConsumer $queueConsumer,
        ConsumerState $consumerState,
        LoggerInterface $logger
    ) {
        parent::__construct($queueConsumer);

        $this->consumerState = $consumerState;
        $this->logger = $logger;
    }

    #[\Override]
    protected function consume(QueueConsumer $consumer, ExtensionInterface $extension): void
    {
        $this->consumerState->startConsumption();
        try {
            parent::consume($consumer, $extension);
        } finally {
            $this->consumerState->stopConsumption();
        }
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    #[\Override]
    protected function getConsumerExtension(array $extensions): ExtensionInterface
    {
        return new ChainExtension($extensions, $this->consumerState);
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @noinspection PhpMissingParentCallCommonInspection
     */
    #[\Override]
    protected function getLoggerExtension(InputInterface $input, OutputInterface $output): ExtensionInterface
    {
        return new LoggerExtension($this->logger);
    }
}
