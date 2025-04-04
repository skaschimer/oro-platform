<?php

namespace Oro\Bundle\DataAuditBundle\Tests\Functional\Controller\Api\Rest;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\DataAuditBundle\Entity\Audit;
use Oro\Bundle\DataAuditBundle\Entity\AuditField;
use Oro\Bundle\DataAuditBundle\Tests\Functional\Environment\Entity\TestAuditDataChild;
use Oro\Bundle\DataAuditBundle\Tests\Functional\Environment\Entity\TestAuditDataOwner;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadUser;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\ResponseExtension;

/**
 * @dbIsolationPerTest
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class AuditControllerTest extends WebTestCase
{
    use ResponseExtension;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient([], self::generateApiAuthHeader());
        $this->loadFixtures([LoadUser::class]);
    }

    private function getEntityManager(): EntityManagerInterface
    {
        return self::getContainer()->get('doctrine')->getManager();
    }

    private function getAdminUser(): User
    {
        return $this->getReference(LoadUser::USER);
    }

    public function testShouldReturn401IfNotAuthenticated()
    {
        $this->client->setServerParameters([]);

        $this->client->jsonRequest('GET', $this->getUrl('oro_api_get_audits'));

        $this->assertLastResponseStatus(401);
        $this->assertLastResponseContentTypeJson();
    }

    public function testShouldAllowGetAvailableAuditsAsArray()
    {
        $user = $this->getAdminUser();

        $em = $this->getEntityManager();

        $audit = new Audit();
        $audit->setAction('anAction');
        $audit->setObjectName('aName');
        $audit->setObjectClass('aClass');
        $audit->setObjectId(123);
        $audit->setTransactionId('anTransactionId');
        $audit->setLoggedAt(new \DateTime('2012-12-12 00:00:00+0000'));
        $audit->setUser($user);
        $audit->setVersion(2);
        $em->persist($audit);

        $anotherAudit = new Audit();
        $anotherAudit->setObjectName('aName');
        $anotherAudit->setObjectClass('aClass');
        $anotherAudit->setObjectId(345);
        $anotherAudit->setTransactionId('anTransactionId');
        $em->persist($anotherAudit);

        $em->flush();

        $this->client->jsonRequest('GET', $this->getUrl('oro_api_get_audits'));

        $this->assertLastResponseStatus(200);
        $this->assertLastResponseContentTypeJson();

        $result = $this->getLastResponseJsonContent();

        $this->assertIsArray($result);
        $this->assertCount(2, $result);

        $actualAudit = $result[0];
        $this->assertEquals($audit->getId(), $actualAudit['id']);
        $this->assertEquals('2012-12-12T00:00:00+00:00', $actualAudit['loggedAt']);
        $this->assertEquals(123, $actualAudit['objectId']);
        $this->assertEquals('aClass', $actualAudit['objectClass']);
        $this->assertEquals('aName', $actualAudit['objectName']);
        $this->assertEquals($user->getId(), $actualAudit['user']);
        $this->assertEquals('anAction', $actualAudit['action']);
    }

    public function testShouldAllowGetAuditById()
    {
        $user = $this->getAdminUser();

        $em = $this->getEntityManager();

        $audit = new Audit();
        $audit->setAction('anAction');
        $audit->setObjectName('aName');
        $audit->setObjectClass('aClass');
        $audit->setObjectId(123);
        $audit->setTransactionId('anTransactionId');
        $audit->setLoggedAt(new \DateTime('2012-12-12 00:00:00+0000'));
        $audit->setUser($user);
        $audit->setVersion(2);
        $em->persist($audit);

        $anotherAudit = new Audit();
        $anotherAudit->setObjectName('aName');
        $anotherAudit->setObjectClass('aClass');
        $anotherAudit->setObjectId(345);
        $anotherAudit->setTransactionId('anTransactionId');
        $em->persist($anotherAudit);

        $em->flush();

        //guard
        $this->assertNotEmpty($audit->getId());

        $this->client->jsonRequest('GET', $this->getUrl('oro_api_get_audit', ['id' => $audit->getId()]));
        $this->assertLastResponseStatus(200);
        $this->assertLastResponseContentTypeJson();

        $result = $this->getLastResponseJsonContent();

        $this->assertIsArray($result);

        $actualAudit = $result;
        $this->assertEquals($audit->getId(), $actualAudit['id']);
        $this->assertEquals('2012-12-12T00:00:00+00:00', $actualAudit['loggedAt']);
        $this->assertEquals(123, $actualAudit['objectId']);
        $this->assertEquals('aClass', $actualAudit['objectClass']);
        $this->assertEquals('aName', $actualAudit['objectName']);
        $this->assertEquals($user->getId(), $actualAudit['user']);
        $this->assertEquals('anAction', $actualAudit['action']);
    }

    public function testShouldAllowGetInformationAboutChangedFields()
    {
        $em = $this->getEntityManager();
        $audit = new Audit();
        $audit->setObjectName('aName');
        $audit->setObjectClass('aClass');
        $audit->setObjectId(123);
        $audit->setTransactionId('anTransactionId');
        $audit->addField(new AuditField('fooField', 'text', 'foo', null));
        $audit->addField(new AuditField('barField', 'text', 'bar2', 'bar1'));
        $em->persist($audit);
        $em->flush();

        //guard
        $this->assertNotEmpty($audit->getId());

        $this->client->jsonRequest('GET', $this->getUrl('oro_api_get_audit', ['id' => $audit->getId()]));
        $this->assertLastResponseStatus(200);
        $this->assertLastResponseContentTypeJson();

        $result = $this->getLastResponseJsonContent();

        $this->assertIsArray($result);

        $this->assertEquals([
            'fooField' => ['old' => null, 'new' => 'foo'],
            'barField' => ['old' => 'bar1', 'new' => 'bar2'],
        ], $result['data']);
    }

    public function testShouldReturnAuditsWithLoggedAtGreatThenOrEqualDate()
    {
        $em = $this->getEntityManager();

        $audit = new Audit();
        $audit->setObjectName('aName');
        $audit->setObjectClass('aClass');
        $audit->setObjectId(123);
        $audit->setTransactionId('aTransactionId1');
        $audit->setLoggedAt(new \DateTime('2012-10-10 00:01+0000'));
        $em->persist($audit);

        $audit = new Audit();
        $audit->setObjectName('aName');
        $audit->setObjectClass('aClass');
        $audit->setObjectId(123);
        $audit->setTransactionId('aTransactionId2');
        $audit->setLoggedAt(new \DateTime('2012-10-12 00:01+0000'));
        $em->persist($audit);

        $audit = new Audit();
        $audit->setObjectName('aName');
        $audit->setObjectClass('aClass');
        $audit->setObjectId(123);
        $audit->setTransactionId('aTransactionId3');
        $audit->setLoggedAt(new \DateTime('2012-10-14 00:01+0000'));
        $em->persist($audit);

        $em->flush();

        $this->client->jsonRequest(
            'GET',
            $this->getUrl('oro_api_get_audits').'?loggedAt>='.urlencode('2012-10-12T00:01+0000')
        );
        $this->assertLastResponseStatus(200);
        $this->assertLastResponseContentTypeJson();

        $result = $this->getLastResponseJsonContent();

        $this->assertIsArray($result);

        $this->assertCount(2, $result);
    }

    public function testShouldReturnAuditsWithLoggedAtGreatThenDate()
    {
        $em = $this->getEntityManager();

        $audit = new Audit();
        $audit->setObjectName('aName');
        $audit->setObjectClass('aClass');
        $audit->setObjectId(123);
        $audit->setTransactionId('aTransactionId1');
        $audit->setLoggedAt(new \DateTime('2012-10-10 00:01+0000'));
        $em->persist($audit);

        $audit = new Audit();
        $audit->setObjectName('aName');
        $audit->setObjectClass('aClass');
        $audit->setObjectId(123);
        $audit->setTransactionId('aTransactionId2');
        $audit->setLoggedAt(new \DateTime('2012-10-12 00:01+0000'));
        $em->persist($audit);

        $audit = new Audit();
        $audit->setObjectName('aName');
        $audit->setObjectClass('aClass');
        $audit->setObjectId(123);
        $audit->setTransactionId('aTransactionId3');
        $audit->setLoggedAt(new \DateTime('2012-10-14 00:01+0000'));
        $em->persist($audit);

        $em->flush();

        $this->client->jsonRequest(
            'GET',
            $this->getUrl('oro_api_get_audits').'?loggedAt>'.urlencode('2012-10-12T00:01+0000')
        );
        $this->assertLastResponseStatus(200);
        $this->assertLastResponseContentTypeJson();

        $result = $this->getLastResponseJsonContent();

        $this->assertIsArray($result);

        $this->assertCount(1, $result);
    }

    public function testShouldReturnAuditsWithLoggedAtLessOrEqualThenDate()
    {
        $em = $this->getEntityManager();

        $audit = new Audit();
        $audit->setObjectName('aName');
        $audit->setObjectClass('aClass');
        $audit->setObjectId(123);
        $audit->setTransactionId('aTransactionId1');
        $audit->setLoggedAt(new \DateTime('2012-10-10 00:01+0000'));
        $em->persist($audit);

        $audit = new Audit();
        $audit->setObjectName('aName');
        $audit->setObjectClass('aClass');
        $audit->setObjectId(123);
        $audit->setTransactionId('aTransactionId2');
        $audit->setLoggedAt(new \DateTime('2012-10-12 00:01+0000'));
        $em->persist($audit);

        $audit = new Audit();
        $audit->setObjectName('aName');
        $audit->setObjectClass('aClass');
        $audit->setObjectId(123);
        $audit->setTransactionId('aTransactionId3');
        $audit->setLoggedAt(new \DateTime('2012-10-14 00:01+0000'));
        $em->persist($audit);

        $em->flush();

        $this->client->jsonRequest(
            'GET',
            $this->getUrl('oro_api_get_audits').'?loggedAt<='.urlencode('2012-10-12T00:01+0000')
        );
        $this->assertLastResponseStatus(200);
        $this->assertLastResponseContentTypeJson();

        $result = $this->getLastResponseJsonContent();

        $this->assertIsArray($result);

        $this->assertCount(2, $result);
    }

    public function testShouldReturnAuditsWithLoggedAtLessThenDate()
    {
        $em = $this->getEntityManager();

        $audit = new Audit();
        $audit->setObjectName('aName');
        $audit->setObjectClass('aClass');
        $audit->setObjectId(123);
        $audit->setTransactionId('aTransactionId1');
        $audit->setLoggedAt(new \DateTime('2012-10-10 00:01+0000'));
        $em->persist($audit);

        $audit = new Audit();
        $audit->setObjectName('aName');
        $audit->setObjectClass('aClass');
        $audit->setObjectId(123);
        $audit->setTransactionId('aTransactionId2');
        $audit->setLoggedAt(new \DateTime('2012-10-12 00:01+0000'));
        $em->persist($audit);

        $audit = new Audit();
        $audit->setObjectName('aName');
        $audit->setObjectClass('aClass');
        $audit->setObjectId(123);
        $audit->setTransactionId('aTransactionId3');
        $audit->setLoggedAt(new \DateTime('2012-10-14 00:01+0000'));
        $em->persist($audit);

        $em->flush();

        $this->client->jsonRequest(
            'GET',
            $this->getUrl('oro_api_get_audits').'?loggedAt<'.urlencode('2012-10-12T00:01+0000')
        );
        $this->assertLastResponseStatus(200);
        $this->assertLastResponseContentTypeJson();

        $result = $this->getLastResponseJsonContent();

        $this->assertIsArray($result);

        $this->assertCount(1, $result);
    }

    public function testShouldReturnAuditsWithActionEqualsToGivenOne()
    {
        $em = $this->getEntityManager();

        $audit = new Audit();
        $audit->setObjectName('aName');
        $audit->setObjectClass('aClass');
        $audit->setObjectId(123);
        $audit->setTransactionId('aTransactionId1');
        $audit->setAction('create');
        $em->persist($audit);

        $audit = new Audit();
        $audit->setObjectName('aName');
        $audit->setObjectClass('aClass');
        $audit->setObjectId(123);
        $audit->setTransactionId('aTransactionId2');
        $audit->setAction('update');
        $em->persist($audit);

        $audit = new Audit();
        $audit->setObjectName('aName');
        $audit->setObjectClass('aClass');
        $audit->setObjectId(123);
        $audit->setTransactionId('aTransactionId3');
        $audit->setAction('remove');
        $em->persist($audit);

        $em->flush();

        $this->client->jsonRequest(
            'GET',
            $this->getUrl('oro_api_get_audits').'?action=create'
        );
        $this->assertLastResponseStatus(200);
        $this->assertLastResponseContentTypeJson();

        $result = $this->getLastResponseJsonContent();

        $this->assertIsArray($result);

        $this->assertCount(1, $result);
    }

    public function testShouldReturnAuditsWithActionNotEqualsToGivenOne()
    {
        $em = $this->getEntityManager();

        $audit = new Audit();
        $audit->setObjectName('aName');
        $audit->setObjectClass('aClass');
        $audit->setObjectId(123);
        $audit->setTransactionId('aTransactionId1');
        $audit->setAction('create');
        $em->persist($audit);

        $audit = new Audit();
        $audit->setObjectName('aName');
        $audit->setObjectClass('aClass');
        $audit->setObjectId(123);
        $audit->setTransactionId('aTransactionId2');
        $audit->setAction('update');
        $em->persist($audit);

        $audit = new Audit();
        $audit->setObjectName('aName');
        $audit->setObjectClass('aClass');
        $audit->setObjectId(123);
        $audit->setTransactionId('aTransactionId3');
        $audit->setAction('remove');
        $em->persist($audit);

        $em->flush();

        $this->client->jsonRequest(
            'GET',
            $this->getUrl('oro_api_get_audits').'?action<>create'
        );
        $this->assertLastResponseStatus(200);
        $this->assertLastResponseContentTypeJson();

        $result = $this->getLastResponseJsonContent();

        $this->assertIsArray($result);

        $this->assertCount(2, $result);
    }

    public function testShouldReturnAuditsWithObjectClassEqualsToGivenOne()
    {
        $em = $this->getEntityManager();

        $audit = new Audit();
        $audit->setObjectName('aName');
        $audit->setObjectClass('aClass');
        $audit->setObjectId(123);
        $audit->setTransactionId('aTransactionId1');
        $audit->setObjectClass(TestAuditDataChild::class);
        $em->persist($audit);

        $audit = new Audit();
        $audit->setObjectName('aName');
        $audit->setObjectClass('aClass');
        $audit->setObjectId(123);
        $audit->setTransactionId('aTransactionId2');
        $audit->setObjectClass(TestAuditDataChild::class);
        $em->persist($audit);

        $audit = new Audit();
        $audit->setObjectName('aName');
        $audit->setObjectClass('aClass');
        $audit->setObjectId(123);
        $audit->setTransactionId('aTransactionId3');
        $audit->setObjectClass(TestAuditDataOwner::class);
        $em->persist($audit);

        $em->flush();

        $this->client->jsonRequest(
            'GET',
            $this->getUrl('oro_api_get_audits').'?objectClass='.TestAuditDataOwner::class
        );
        $this->assertLastResponseStatus(200);
        $this->assertLastResponseContentTypeJson();

        $result = $this->getLastResponseJsonContent();

        $this->assertIsArray($result);

        $this->assertCount(1, $result);
    }

    public function testShouldReturnAuditsWithObjectClassNotEqualsToGivenOne()
    {
        $em = $this->getEntityManager();

        $audit = new Audit();
        $audit->setObjectName('aName');
        $audit->setObjectClass('aClass');
        $audit->setObjectId(123);
        $audit->setTransactionId('aTransactionId1');
        $audit->setObjectClass(TestAuditDataChild::class);
        $em->persist($audit);

        $audit = new Audit();
        $audit->setObjectName('aName');
        $audit->setObjectClass('aClass');
        $audit->setObjectId(123);
        $audit->setTransactionId('aTransactionId2');
        $audit->setObjectClass(TestAuditDataChild::class);
        $em->persist($audit);

        $audit = new Audit();
        $audit->setObjectName('aName');
        $audit->setObjectClass('aClass');
        $audit->setObjectId(123);
        $audit->setTransactionId('aTransactionId3');
        $audit->setObjectClass(TestAuditDataOwner::class);
        $em->persist($audit);

        $em->flush();

        $this->client->jsonRequest(
            'GET',
            $this->getUrl('oro_api_get_audits').'?objectClass<>'.TestAuditDataOwner::class
        );
        $this->assertLastResponseStatus(200);
        $this->assertLastResponseContentTypeJson();

        $result = $this->getLastResponseJsonContent();

        $this->assertIsArray($result);

        $this->assertCount(2, $result);
    }

    public function testShouldReturnAuditsWithUserEqualsToGivenOne()
    {
        $user = $this->getAdminUser();

        $em = $this->getEntityManager();

        $audit = new Audit();
        $audit->setObjectName('aName');
        $audit->setObjectClass('aClass');
        $audit->setObjectId(123);
        $audit->setTransactionId('aTransactionId1');
        $audit->setUser($user);
        $em->persist($audit);

        $audit = new Audit();
        $audit->setObjectName('aName');
        $audit->setObjectClass('aClass');
        $audit->setObjectId(123);
        $audit->setTransactionId('aTransactionId2');
        $audit->setUser(null);
        $em->persist($audit);

        $audit = new Audit();
        $audit->setObjectName('aName');
        $audit->setObjectClass('aClass');
        $audit->setObjectId(123);
        $audit->setTransactionId('aTransactionId3');
        $audit->setUser(null);
        $em->persist($audit);

        $em->flush();

        $this->client->jsonRequest(
            'GET',
            $this->getUrl('oro_api_get_audits').'?user='.$user->getId()
        );
        $this->assertLastResponseStatus(200);
        $this->assertLastResponseContentTypeJson();

        $result = $this->getLastResponseJsonContent();

        $this->assertIsArray($result);

        $this->assertCount(1, $result);
    }

    public function testShouldReturnNothingIfGivenUserDoesNotExist()
    {
        $user = $this->getAdminUser();

        $em = $this->getEntityManager();
        $audit = new Audit();
        $audit->setObjectName('aName');
        $audit->setObjectClass('aClass');
        $audit->setObjectId(123);
        $audit->setTransactionId('aTransactionId');
        $audit->setUser($user);
        $em->persist($audit);
        $em->flush();

        $this->client->jsonRequest(
            'GET',
            $this->getUrl('oro_api_get_audits').'?user=0'
        );
        $this->assertLastResponseStatus(200);
        $this->assertLastResponseContentTypeJson();

        $result = $this->getLastResponseJsonContent();

        $this->assertIsArray($result);

        $this->assertCount(0, $result);
    }
}
