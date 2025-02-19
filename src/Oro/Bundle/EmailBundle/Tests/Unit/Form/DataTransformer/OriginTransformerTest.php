<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Form\DataTransformer;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\EmailBundle\Entity\EmailOrigin;
use Oro\Bundle\EmailBundle\Form\DataTransformer\OriginTransformer;
use Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity\TestEmailOrigin;
use Oro\Bundle\EmailBundle\Tools\EmailOriginHelper;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Symfony\Component\Form\Exception\UnexpectedTypeException;

class OriginTransformerTest extends \PHPUnit\Framework\TestCase
{
    private const TEST_ID = 101;

    /** @var OriginTransformer */
    private $transformer;
    /** @var EntityManager */
    private $entityManagerMock;

    #[\Override]
    protected function setUp(): void
    {
        $this->entityManagerMock = $this->createMock(EntityManager::class);

        $tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $emailOriginHelperMock = $this->createMock(EmailOriginHelper::class);

        $this->transformer = new OriginTransformer(
            $this->entityManagerMock,
            $tokenAccessor,
            $emailOriginHelperMock
        );
    }

    public function testTransform()
    {
        $testOrigin = new TestEmailOrigin(self::TEST_ID);
        $this->assertEquals(self::TEST_ID, $this->transformer->transform($testOrigin));
    }

    public function testTransformFail()
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->transformer->transform('object should be provided');
    }

    public function testReverseTransformSystemEmail()
    {
        $testOrigin = new TestEmailOrigin(1);

        $this->entityManagerMock->expects($this->any())
            ->method('find')
            ->with(EmailOrigin::class, 1)
            ->willReturn($testOrigin);

        $this->assertEquals($testOrigin, $this->transformer->reverseTransform('1|mail@example.com'));
    }
}
