<?php

namespace Oro\Bundle\IntegrationBundle\ActionHandler;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\IntegrationBundle\Entity\Channel;

class ChannelEnableActionHandler implements ChannelActionHandlerInterface
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[\Override]
    public function handleAction(Channel $channel)
    {
        $channel->setPreviouslyEnabled($channel->isEnabled());
        $channel->setEnabled(true);

        $this->entityManager->flush();

        return true;
    }
}
