<?php

namespace Oro\Bundle\ReminderBundle\Tests\Unit\Model\Email;

use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\EmailBundle\Model\EmailTemplateCriteria;
use Oro\Bundle\EmailBundle\Model\From;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\ReminderBundle\Entity\Reminder;
use Oro\Bundle\ReminderBundle\Exception\InvalidArgumentException;
use Oro\Bundle\ReminderBundle\Model\Email\TemplateEmailNotification;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class TemplateEmailNotificationTest extends TestCase
{
    use EntityTrait;

    private ObjectManager&MockObject $objectManager;
    private ConfigProvider&MockObject $configProvider;
    private EntityNameResolver&MockObject $entityNameResolver;
    private TemplateEmailNotification $templateEmailNotification;

    #[\Override]
    protected function setUp(): void
    {
        $this->objectManager = $this->createMock(ObjectManager::class);
        $this->configProvider = $this->createMock(ConfigProvider::class);
        $this->entityNameResolver = $this->createMock(EntityNameResolver::class);
        $this->templateEmailNotification = new TemplateEmailNotification(
            $this->objectManager,
            $this->configProvider,
            $this->entityNameResolver
        );
    }

    public function testGetRecipients(): void
    {
        $recipient = new User();
        $reminder = $this->getEntity(Reminder::class, [
            'recipient' => $recipient,
        ]);

        $this->templateEmailNotification->setReminder($reminder);
        self::assertEquals([$recipient], $this->templateEmailNotification->getRecipients());
    }

    public function testGetRecipientsWhenNoReminder(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Reminder was not set');
        $this->templateEmailNotification->getRecipients();
    }

    public function testGetTemplateCriteria(): void
    {
        $entityClassName = \stdClass::class;
        $reminder = $this->getEntity(Reminder::class, [
            'relatedEntityClassName' => $entityClassName,
        ]);
        $config = $this->createMock(ConfigInterface::class);
        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->with($entityClassName)
            ->willReturn($config);
        $templateName = 'some_template';
        $config->expects($this->once())
            ->method('get')
            ->with(TemplateEmailNotification::CONFIG_FIELD, true)
            ->willReturn($templateName);

        $this->templateEmailNotification->setReminder($reminder);
        self::assertEquals(
            new EmailTemplateCriteria($templateName, $entityClassName),
            $this->templateEmailNotification->getTemplateCriteria()
        );
    }

    public function testGetTemplateCriteriaWhenNoReminder(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Reminder was not set');
        $this->templateEmailNotification->getTemplateCriteria();
    }

    public function testGetEntity(): void
    {
        $expectedEntity = new User();

        $entityId = 247;
        $reminder = $this->getEntity(Reminder::class, [
            'relatedEntityClassName' => User::class,
            'relatedEntityId' => $entityId
        ]);

        $entityRepository = $this->createMock(EntityRepository::class);
        $entityRepository->expects($this->any())
            ->method('find')
            ->with($entityId)
            ->willReturn($expectedEntity);

        $this->objectManager->expects($this->any())
            ->method('getRepository')
            ->willReturnMap([
                [User::class, $entityRepository]
            ]);

        $this->templateEmailNotification->setReminder($reminder);
        self::assertEquals($expectedEntity, $this->templateEmailNotification->getEntity());
    }

    public function testGetSenderWhenReminderHasSender(): void
    {
        $senderEmail = 'sender@mail.com';
        $senderName = 'senderName';
        $sender = (new User())->setEmail($senderEmail);

        $reminder = $this->getEntity(Reminder::class, ['sender' => $sender]);

        $this->entityNameResolver->expects($this->any())
            ->method('getName')
            ->with($sender)
            ->willReturn($senderName);

        $expectedSender = From::emailAddress($senderEmail, $senderName);
        $this->templateEmailNotification->setReminder($reminder);
        self::assertEquals($expectedSender, $this->templateEmailNotification->getSender());
    }

    public function testGetSenderWhenReminderHasNoSender(): void
    {
        $reminder = $this->getEntity(Reminder::class);

        $this->templateEmailNotification->setReminder($reminder);
        self::assertNull($this->templateEmailNotification->getSender());
    }
}
