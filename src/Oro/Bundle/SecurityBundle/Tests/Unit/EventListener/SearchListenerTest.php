<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\EventListener;

use Oro\Bundle\SearchBundle\Event\PrepareEntityMapEvent;
use Oro\Bundle\SearchBundle\Event\SearchMappingCollectEvent;
use Oro\Bundle\SecurityBundle\EventListener\SearchListener;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadata;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProviderInterface;
use Oro\Bundle\SecurityBundle\Tests\Unit\Fixtures\Models\CMS\CmsArticle;
use Oro\Bundle\SecurityBundle\Tests\Unit\Fixtures\Models\CMS\CmsOrganization;
use Oro\Bundle\TestFrameworkBundle\Entity\Item;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SearchListenerTest extends TestCase
{
    private OwnershipMetadataProviderInterface&MockObject $metadataProvider;
    private SearchListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->metadataProvider = $this->createMock(OwnershipMetadataProviderInterface::class);

        $this->listener = new SearchListener($this->metadataProvider);
    }

    public function testCollectEntityMapEvent(): void
    {
        $metadata = new OwnershipMetadata('USER', 'owner', 'owner_id', 'organization', 'organization_id');
        $this->metadataProvider->expects($this->once())
            ->method('getMetadata')
            ->willReturn($metadata);

        $event = new SearchMappingCollectEvent([
            Item::class => [
                'alias' => 'oro_test_item',
            ],
        ]);

        $this->listener->collectEntityMapEvent($event);

        $this->assertEquals(
            [
                Item::class => [
                    'alias' => 'oro_test_item',
                    'fields' => [
                        [
                            'name' => 'organization',
                            'target_type' => 'integer',
                            'target_fields' => ['organization'],
                        ],
                        [
                            'name' => 'owner',
                            'target_type' => 'integer',
                            'target_fields' => ['oro_test_item_owner'],
                        ],
                    ],
                ],
            ],
            $event->getMappingConfig()
        );
    }

    public function testPrepareEntityMapEvent(): void
    {
        $entity = new CmsArticle();
        $organization = new CmsOrganization();
        $organization->id = 3;
        $entity->setOrganization($organization);
        $data = [
            'integer' => [
                'organization' => null
            ]
        ];

        $metadata = new OwnershipMetadata('USER', 'user', 'user_id', 'organization', 'organization_id');
        $this->metadataProvider->expects($this->once())
            ->method('getMetadata')
            ->willReturn($metadata);

        $event = new PrepareEntityMapEvent($entity, get_class($entity), $data, ['alias' => 'test']);
        $this->listener->prepareEntityMapEvent($event);
        $resultData = $event->getData();

        $this->assertEquals(3, $resultData['integer']['organization']);
        $this->assertEquals(0, $resultData['integer']['test_user']);
    }
}
