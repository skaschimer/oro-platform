<?php

namespace Oro\Bundle\AddressBundle\Tests\Unit\Entity;

use Oro\Bundle\AddressBundle\Entity\AddressType;

class AddressTypeTest extends \PHPUnit\Framework\TestCase
{
    /** @var AddressType */
    private $type;

    #[\Override]
    protected function setUp(): void
    {
        $this->type = new AddressType('billing');
    }

    public function testName()
    {
        $this->assertEquals('billing', $this->type->getName());
    }

    public function testLabel()
    {
        $this->assertNull($this->type->getLabel());

        $this->type->setLabel('Billing');

        $this->assertEquals('Billing', $this->type->getLabel());
    }

    public function testLocale()
    {
        $this->assertNull($this->type->getLocale());

        $this->type->setLocale('en');

        $this->assertEquals('en', $this->type->getLocale());
    }

    public function testToString()
    {
        $this->assertEquals('', $this->type);

        $this->type->setLabel('Shipping');

        $this->assertEquals('Shipping', (string)$this->type);
    }
}
