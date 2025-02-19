<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional\Helper;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\TestFrameworkBundle\Entity\WorkflowAwareEntity;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WorkflowBundle\Tests\Functional\DataFixtures\LoadWorkflowDefinitions;

class WorkflowAwareEntityFetcherTest extends WebTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadWorkflowDefinitions::class]);
    }

    public function testGetEntitiesWithoutWorkflowItem()
    {
        $workflowManager = $this->getContainer()->get('oro_workflow.manager');

        $entity1 = $this->createWorkflowAwareEntity('test1');
        $entity2 = $this->createWorkflowAwareEntity('test2');

        $this->assertNull($workflowManager->getWorkflowItem($entity1, LoadWorkflowDefinitions::WITH_GROUPS1));
        $this->assertNull($workflowManager->getWorkflowItem($entity2, LoadWorkflowDefinitions::WITH_GROUPS1));

        $workflow = $workflowManager->getWorkflow(LoadWorkflowDefinitions::WITH_GROUPS1);
        $this->assertNotNull($workflow);

        $helper = $this->getContainer()->get('oro_workflow.helper.workflow_aware_entity_fetcher');

        $result = $helper->getEntitiesWithoutWorkflowItem($workflow->getDefinition());

        $this->assertCount(2, $result);
        $this->assertContains($entity1, $result);
        $this->assertContains($entity2, $result);

        $this->assertEquals(
            [$entity1],
            $helper->getEntitiesWithoutWorkflowItem($workflow->getDefinition(), "e.name = 'test1'")
        );

        $this->assertEquals(
            [],
            $helper->getEntitiesWithoutWorkflowItem($workflow->getDefinition(), "e.name = 'test'")
        );
    }

    private function createWorkflowAwareEntity(string $name): WorkflowAwareEntity
    {
        $obj = new WorkflowAwareEntity();
        $obj->setName($name);

        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get('doctrine')->getManagerForClass(WorkflowAwareEntity::class);
        $em->persist($obj);
        $em->flush();

        return $obj;
    }
}
