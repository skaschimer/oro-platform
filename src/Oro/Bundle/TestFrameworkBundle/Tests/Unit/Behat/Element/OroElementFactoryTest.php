<?php

namespace Oro\Bundle\TestFrameworkBundle\Tests\Unit\Behat\Element;

use Behat\Mink\Mink;
use Behat\Mink\Selector\SelectorsHandler;
use Behat\Mink\Session;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroElementFactory;

class OroElementFactoryTest extends \PHPUnit\Framework\TestCase
{
    public function testCreateElementException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Could not find element with "\w+" name/');

        $this->getElementFactory()->createElement('someElement');
    }

    public function testCreateElement()
    {
        $class = 'Oro\Bundle\TestFrameworkBundle\Behat\Element\Element';
        $element = $this->getElementFactory([
            'Test Oro Behat Element' => [
                'class' => $class,
                'selector' => 'body'
            ]
        ])->createElement('Test Oro Behat Element');

        $this->assertInstanceOf($class, $element);
    }

    /**
     * @param array $configuration
     * @return \PHPUnit\Framework\MockObject\MockObject|OroElementFactory
     */
    protected function getElementFactory(array $configuration = [])
    {
        $selectorsHandler = $this->createMock('Behat\Mink\Selector\SelectorsHandler');
        $selectorsHandler->expects($this->any())
            ->method('selectorToXpath')
            ->willReturnArgument(0);
        $session = new Session(
            $this->createMock('Behat\Mink\Driver\DriverInterface'),
            $selectorsHandler
        );
        $mink = new Mink(['default' => $session]);
        $mink->setDefaultSessionName('default');
        $factory = new OroElementFactory($mink, new SelectorsHandler(), $configuration);

        return $factory;
    }
}
