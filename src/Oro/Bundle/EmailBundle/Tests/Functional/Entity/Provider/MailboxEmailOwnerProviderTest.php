<?php

namespace Oro\Bundle\EmailBundle\Tests\Functional\Entity\Provider;

use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\EmailBundle\Entity\Mailbox;
use Oro\Bundle\EmailBundle\Entity\Provider\EmailOwnerProviderInterface;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class MailboxEmailOwnerProviderTest extends WebTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([
            '@OroEmailBundle/Tests/Functional/Entity/Provider/DataFixtures/mailbox_email_owner_provider.yml'
        ]);
    }

    private function getProvider(): EmailOwnerProviderInterface
    {
        return self::getContainer()->get('oro_email.mailbox_email_owner_provider');
    }

    private function getEntityManager(): EntityManagerInterface
    {
        return self::getContainer()->get('doctrine')->getManagerForClass(Mailbox::class);
    }

    private function assertCaseInsensitiveSearchSupported(): void
    {
        $em = $this->getEntityManager();
        $conn = $em->getConnection();
        if ($conn->getDatabasePlatform() instanceof MySqlPlatform) {
            $supported = (bool)$conn->fetchAllAssociative(
                'SELECT 1 FROM information_schema.columns WHERE '
                . 'TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ? AND COLLATION_NAME LIKE ? LIMIT 1;',
                [$conn->getDatabase(), $em->getClassMetadata(Mailbox::class)->getTableName(), 'email', '%_ci']
            );
            if (!$supported) {
                self::markTestSkipped('Case insensitive email search is not supported.');
            }
        }
    }

    public function caseInsensitiveSearchDataProvider(): array
    {
        return [[true], [false]];
    }

    public function testGetEmailOwnerClass(): void
    {
        self::assertEquals(Mailbox::class, $this->getProvider()->getEmailOwnerClass());
    }

    /**
     * @dataProvider caseInsensitiveSearchDataProvider
     */
    public function testFindEmailOwner(bool $caseInsensitiveSearch): void
    {
        $email = 'jane.smith@example.com';
        if ($caseInsensitiveSearch) {
            $this->assertCaseInsensitiveSearchSupported();
            $email = strtoupper($email);
        }

        /** @var Mailbox $owner */
        $owner = $this->getProvider()->findEmailOwner($this->getEntityManager(), $email);
        self::assertInstanceOf(Mailbox::class, $owner);
        self::assertSame($this->getReference('mailbox3')->getId(), $owner->getId());
    }

    public function testFindEmailOwnerWhenItDoesNotExist(): void
    {
        $owner = $this->getProvider()->findEmailOwner($this->getEntityManager(), 'another@example.com');
        self::assertNull($owner);
    }

    /**
     * @dataProvider caseInsensitiveSearchDataProvider
     */
    public function testGetOrganizations(bool $caseInsensitiveSearch): void
    {
        $email = 'jane.smith@example.com';
        if ($caseInsensitiveSearch) {
            $this->assertCaseInsensitiveSearchSupported();
            $email = strtoupper($email);
        }

        $organizations = $this->getProvider()->getOrganizations($this->getEntityManager(), $email);
        self::assertSame(
            [$this->getReference('organization')->getId()],
            $organizations
        );
    }

    public function testGetEmails(): void
    {
        $emails = $this->getProvider()->getEmails(
            $this->getEntityManager(),
            $this->getReference('organization')->getId()
        );
        self::assertSame(
            ['john.smith@example.com', 'test@example.com', 'jane.smith@example.com'],
            iterator_to_array($emails)
        );
    }
}
