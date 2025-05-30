<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Connector;

use Oro\Bundle\ImapBundle\Connector\ImapConfig;
use PHPUnit\Framework\TestCase;

class ImapConfigTest extends TestCase
{
    public function testConstructor(): void
    {
        $host = 'testHost';
        $port = 'testPort';
        $ssl = 'testSsl';
        $user = 'testUser';
        $password = 'testPwd';
        $token = 'testToken';
        $obj = new ImapConfig($host, $port, $ssl, $user, $password, $token);

        $this->assertEquals($host, $obj->getHost());
        $this->assertEquals($port, $obj->getPort());
        $this->assertEquals($ssl, $obj->getSsl());
        $this->assertEquals($user, $obj->getUser());
        $this->assertEquals($password, $obj->getPassword());
        $this->assertEquals($token, $obj->getAccessToken());
    }

    public function testSettersAndGetters(): void
    {
        $obj = new ImapConfig();

        $host = 'testHost';
        $port = 'testPort';
        $ssl = 'testSsl';
        $user = 'testUser';
        $password = 'testPwd';
        $token = 'testToken';

        $obj->setHost($host);
        $obj->setPort($port);
        $obj->setSsl($ssl);
        $obj->setUser($user);
        $obj->setPassword($password);
        $obj->setAccessToken($token);

        $this->assertEquals($host, $obj->getHost());
        $this->assertEquals($port, $obj->getPort());
        $this->assertEquals($ssl, $obj->getSsl());
        $this->assertEquals($user, $obj->getUser());
        $this->assertEquals($password, $obj->getPassword());
        $this->assertEquals($token, $obj->getAccessToken());
    }
}
