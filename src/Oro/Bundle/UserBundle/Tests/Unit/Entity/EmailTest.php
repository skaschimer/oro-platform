<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Entity;

use Oro\Bundle\UserBundle\Entity\Email;
use Oro\Bundle\UserBundle\Entity\User;

class EmailTest extends \PHPUnit\Framework\TestCase
{
    private $user;

    private $email;

    #[\Override]
    protected function setUp(): void
    {
        $this->user = $this->createMock(User::class);
        $this->email = new Email();
    }

    public function testEmail()
    {
        $email = 'email@example.com';
        $this->assertNull($this->email->getEmail());
        $this->email->setEmail($email);
        $this->assertEquals($email, $this->email->getEmail());
    }

    public function testId()
    {
        $this->assertNull($this->email->getId());
    }

    public function testUser()
    {
        $this->assertNull($this->email->getUser());
        $this->email->setUser($this->user);
        $this->assertEquals($this->user, $this->email->getUser());
    }
}
