<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Imagine\Factory;

use Oro\Bundle\AttachmentBundle\Imagine\Factory\ClassFactory;
use Oro\Bundle\AttachmentBundle\Imagine\Loader\Loader;
use PHPUnit\Framework\TestCase;

class ClassFactoryTest extends TestCase
{
    private ClassFactory $factory;

    #[\Override]
    protected function setUp(): void
    {
        $this->factory = new ClassFactory('gaufrette://');
    }

    public function testCreateFileLoader(): void
    {
        $loader = $this->factory->createFileLoader(__DIR__ . '/files/original_attachment.jpg');
        $this->assertInstanceOf(Loader::class, $loader);
    }
}
