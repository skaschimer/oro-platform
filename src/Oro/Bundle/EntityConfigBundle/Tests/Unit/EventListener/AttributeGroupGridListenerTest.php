<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\EventListener\AttributeGroupGridListener;
use Oro\Bundle\EntityConfigBundle\Manager\AttributeManager;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AttributeGroupGridListenerTest extends TestCase
{
    use EntityTrait;

    private AttributeGroupGridListener $listener;
    private AttributeManager&MockObject $attributeManager;

    #[\Override]
    protected function setUp(): void
    {
        $this->attributeManager = $this->createMock(AttributeManager::class);

        $this->listener = new AttributeGroupGridListener($this->attributeManager);
    }

    public function testOnResultAfter(): void
    {
        $groupId1 = 1;
        $groupId2 = 2;
        $record1 = new ResultRecord(['id' => $groupId1]);
        $record2 = new ResultRecord(['id' => $groupId2]);
        $records = [$record1, $record2];

        $datagrid = $this->createMock(DatagridInterface::class);
        $event = new OrmResultAfter($datagrid, $records);

        $attributeId1 = 5;
        $attribute1 = $this->getAttribute($attributeId1);
        $attributeId2 = 6;
        $attribute2 = $this->getAttribute($attributeId1);
        $attributeId3 = 7;
        $attribute3 = $this->getAttribute($attributeId1);
        $notExistingAttribute = 999;

        $this->attributeManager->expects($this->once())
            ->method('getAttributesMapByGroupIds')
            ->with([$groupId1, $groupId2])
            ->willReturn([
                $groupId1 => [$attributeId1],
                $groupId2 => [$attributeId2, $attributeId3, $notExistingAttribute]
            ]);
        $this->attributeManager->expects($this->once())
            ->method('getAttributesByIdsWithIndex')
            ->with([$attributeId1, $attributeId2, $attributeId3, $notExistingAttribute])
            ->willReturn([
                $attributeId1 => $attribute1,
                $attributeId2 => $attribute2,
                $attributeId3 => $attribute3,
            ]);
        $this->attributeManager->expects($this->exactly(3))
            ->method('getAttributeLabel')
            ->withConsecutive(
                [$attribute1],
                [$attribute2],
                [$attribute3]
            )
            ->willReturnOnConsecutiveCalls(
                'label1',
                'label2',
                'label3'
            );

        $this->listener->onResultAfter($event);

        $record1->addData(['attributes' => ['label1']]);
        $record2->addData(['attributes' => ['label2', 'label3']]);
        $expectedRecords = [
            $record1,
            $record2
        ];
        $this->assertEquals($expectedRecords, $event->getRecords());
    }

    private function getAttribute($id)
    {
        return $this->getEntity(FieldConfigModel::class, ['id' => $id]);
    }
}
