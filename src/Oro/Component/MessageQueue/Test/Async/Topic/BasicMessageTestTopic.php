<?php

namespace Oro\Component\MessageQueue\Test\Async\Topic;

use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * A test topic for basic message processor
 */
class BasicMessageTestTopic extends AbstractTopic
{
    #[\Override]
    public static function getName(): string
    {
        return 'oro.message_queue.basic_message_processor';
    }

    #[\Override]
    public static function getDescription(): string
    {
        return 'Test topic for basic message processor.';
    }

    #[\Override]
    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefined('message')
            ->setAllowedTypes('message', 'string');
    }
}
