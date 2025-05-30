<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Manager\DTO;

use Laminas\Mail\Header\ContentType;
use Oro\Bundle\ImapBundle\Mail\Storage\Attachment;
use Oro\Bundle\ImapBundle\Mail\Storage\Body;
use Oro\Bundle\ImapBundle\Mail\Storage\Content;
use Oro\Bundle\ImapBundle\Mail\Storage\Message;
use Oro\Bundle\ImapBundle\Mail\Storage\Value;
use Oro\Bundle\ImapBundle\Manager\DTO\Email;
use Oro\Bundle\ImapBundle\Manager\DTO\EmailAttachment;
use Oro\Bundle\ImapBundle\Manager\DTO\EmailBody;
use Oro\Bundle\ImapBundle\Manager\DTO\ItemId;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class EmailTest extends TestCase
{
    private Message&MockObject $message;
    private Email $email;

    #[\Override]
    protected function setUp(): void
    {
        $this->message = $this->createMock(Message::class);

        $this->email = new Email($this->message);
    }

    /**
     * @dataProvider getBodyDataProvider
     */
    public function testGetBody(?ContentType $contentType, bool $bodyIsText, string $expectedContentType): void
    {
        $this->assertGetBodyCalled($contentType, $bodyIsText);

        $body = new EmailBody();
        $body->setContent('testContent')
            ->setBodyIsText($bodyIsText)
            ->setOriginalContentType($expectedContentType);

        $this->assertEquals($body, $this->email->getBody());

        //assert data from local cache
        $this->assertEquals($body, $this->email->getBody());
    }

    public function getBodyDataProvider(): array
    {
        return [
            'text/plain content type' => [
                'contentType' => ContentType::fromString('Content-Type: text/plain'),
                'bodyIsText' => true,
                'expectedContentType' => 'text/plain',
            ],
            'text/html content type' => [
                'contentType' => ContentType::fromString('Content-Type: text/html'),
                'bodyIsText' => false,
                'expectedContentType' => 'text/html',
            ],
            'empty content type' => [
                'contentType' => null,
                'bodyIsText' => true,
                'expectedContentType' => '',
            ],
        ];
    }

    protected function assertGetBodyCalled(?ContentType $contentType, bool $bodyIsText)
    {
        $srcBodyContent = $this->createMock(Content::class);
        $srcBodyContent->expects($this->once())
            ->method('getDecodedContent')
            ->willReturn('testContent');

        $srcBody = $this->createMock(Body::class);
        $srcBody->expects($this->once())
            ->method('getContent')
            ->with(!$bodyIsText)
            ->willReturn($srcBodyContent);

        $this->message->expects($this->once())
            ->method('getBody')
            ->willReturn($srcBody);

        $this->message->expects($this->once())
            ->method('getPriorContentType')
            ->willReturn($contentType);
    }

    /**
     * @dataProvider getAttachmentsDataProvider
     */
    public function testGetAttachments(
        array $attachments,
        bool $getBodyCalled,
        ?ContentType $contentType = null,
        ?Attachment $msgAsAttachment = null,
        array $expected = []
    ): void {
        $this->message->expects($this->once())
            ->method('getAttachments')
            ->willReturn($attachments);

        if ($getBodyCalled) {
            $this->assertGetBodyCalled($contentType, true);
        }

        if ($msgAsAttachment) {
            $this->message->expects($this->once())
                ->method('getMessageAsAttachment')
                ->willReturn($msgAsAttachment);
        }

        $this->assertEquals($expected, $this->email->getAttachments());
    }

    public function getAttachmentsDataProvider(): array
    {
        $attachment = new EmailAttachment();
        $attachment
            ->setFileName('fileName')
            ->setContent('content')
            ->setContentType('contentType')
            ->setContentTransferEncoding('contentTransferEncoding');

        return [
            'with attachments' => [
                'attachments' => [$this->getAttachment()],
                'getBodyCalled' => false,
                'contentType' => null,
                'messageAsAttachment' => null,
                'expected' => [$attachment]
            ],
            'without attachments, message as attachment' => [
                'attachments' => [],
                'getBodyCalled' => true,
                'contentType' => null,
                'messageAsAttachment' => $this->getAttachment(),
                'expected' => [$attachment]
            ],
            'without any attachments' => [
                'attachments' => [],
                'getBodyCalled' => true,
                'contentType' => ContentType::fromString('Content-Type: text/plain'),
                'messageAsAttachment' => null,
                'expected' => []
            ],
        ];
    }

    protected function getAttachment(): Attachment&MockObject
    {
        $srcAttachmentContent = $this->createMock(Content::class);
        $srcAttachmentContent->expects($this->once())
            ->method('getContent')
            ->willReturn('content');
        $srcAttachmentContent->expects($this->once())
            ->method('getContentType')
            ->willReturn('contentType');
        $srcAttachmentContent->expects($this->once())
            ->method('getContentTransferEncoding')
            ->willReturn('contentTransferEncoding');

        $srcAttachmentFileName = $this->createMock(Value::class);
        $srcAttachmentFileName->expects($this->once())
            ->method('getValue')
            ->willReturn('fileName');

        $srcAttachment = $this->createMock(Attachment::class);
        $srcAttachment->expects($this->once())
            ->method('getFileName')
            ->willReturn($srcAttachmentFileName);
        $srcAttachment->expects($this->once())
            ->method('getContent')
            ->willReturn($srcAttachmentContent);

        return $srcAttachment;
    }

    public function testGettersAndSetters(): void
    {
        $id = new ItemId('testId', 'testChangeKey');
        $sentAt = new \DateTime('now');
        $receivedAt = new \DateTime('now');
        $internalDate = new \DateTime('now');
        $flags = ["\\Test"];

        $this->email
            ->setId($id)
            ->setSubject('testSubject')
            ->setFrom('testFrom')
            ->addToRecipient('testToRecipient')
            ->addCcRecipient('testCcRecipient')
            ->addBccRecipient('testBccRecipient')
            ->setSentAt($sentAt)
            ->setReceivedAt($receivedAt)
            ->setInternalDate($internalDate)
            ->setImportance(1)
            ->setMessageId('testMessageId')
            ->setMultiMessageId(['testMessageId1','testMessageId2'])
            ->setXMessageId('testXMessageId')
            ->setXThreadId('testXThreadId');

        $this->assertEquals($id, $this->email->getId());
        $this->assertEquals('testSubject', $this->email->getSubject());
        $this->assertEquals('testFrom', $this->email->getFrom());
        $this->assertEquals('testToRecipient', $this->email->getToRecipients()[0]);
        $this->assertEquals('testCcRecipient', $this->email->getCcRecipients()[0]);
        $this->assertEquals('testBccRecipient', $this->email->getBccRecipients()[0]);
        $this->assertEquals($sentAt, $this->email->getSentAt());
        $this->assertEquals($receivedAt, $this->email->getReceivedAt());
        $this->assertEquals($internalDate, $this->email->getInternalDate());
        $this->assertEquals(1, $this->email->getImportance());
        $this->assertEquals('testMessageId', $this->email->getMessageId());
        $this->assertEquals('testXMessageId', $this->email->getXMessageId());
        $this->assertEquals('testXThreadId', $this->email->getXThreadId());
        $this->assertCount(2, $this->email->getMultiMessageId());
        $this->assertIsArray($this->email->getMultiMessageId());

        $this->message->expects($this->exactly(2))
            ->method('getFlags')
            ->willReturn($flags);

        $this->assertTrue($this->email->hasFlag("\\Test"));
        $this->assertFalse($this->email->hasFlag("\\Test2"));
    }
}
