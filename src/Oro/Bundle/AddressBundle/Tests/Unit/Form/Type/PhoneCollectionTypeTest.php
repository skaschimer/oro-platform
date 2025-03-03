<?php

namespace Oro\Bundle\AddressBundle\Tests\Unit\Form\Type;

use Oro\Bundle\AddressBundle\Form\Type\PhoneCollectionType;
use Oro\Bundle\FormBundle\Form\Type\CollectionType;

class PhoneCollectionTypeTest extends \PHPUnit\Framework\TestCase
{
    /** @var PhoneCollectionType */
    private $type;

    #[\Override]
    protected function setUp(): void
    {
        $this->type = new PhoneCollectionType();
    }

    public function testGetParent()
    {
        $this->assertEquals(CollectionType::class, $this->type->getParent());
    }

    public function testGetName()
    {
        $this->assertEquals('oro_phone_collection', $this->type->getName());
    }
}
