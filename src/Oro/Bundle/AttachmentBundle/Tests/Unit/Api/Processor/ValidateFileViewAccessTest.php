<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Api\Processor;

use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Get\GetProcessorTestCase;
use Oro\Bundle\AttachmentBundle\Api\Processor\ValidateFileViewAccess;
use Oro\Bundle\AttachmentBundle\Entity\File;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class ValidateFileViewAccessTest extends GetProcessorTestCase
{
    private AuthorizationCheckerInterface&MockObject $authorizationChecker;
    private ValidateFileViewAccess $processor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);

        $this->processor = new ValidateFileViewAccess($this->authorizationChecker);
    }

    public function testProcessWhenAccessGranted(): void
    {
        $action = 'get';
        $fileClass = File::class;
        $fileId = 123;

        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('VIEW', new ObjectIdentity($fileId, $fileClass))
            ->willReturn(true);

        $this->context->setAction($action);
        $this->context->setClassName($fileClass);
        $this->context->setId($fileId);
        $this->processor->process($this->context);

        self::assertEquals($this->context->getSharedData()->get('granted_view_access'), [$action, $fileClass, $fileId]);
    }

    public function testProcessWhenAccessDenied(): void
    {
        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('No access to the entity.');

        $fileClass = File::class;
        $fileId = 123;

        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('VIEW', new ObjectIdentity($fileId, $fileClass))
            ->willReturn(false);

        $this->context->setClassName($fileClass);
        $this->context->setId($fileId);
        $this->processor->process($this->context);

        self::assertFalse($this->context->getSharedData()->has('granted_view_access'));
    }
}
