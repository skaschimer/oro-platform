<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Config;

use Oro\Bundle\ApiBundle\Config\ActionFieldConfig;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ActionFieldConfigTest extends TestCase
{
    public function testClone(): void
    {
        $config = new ActionFieldConfig();
        self::assertEmpty($config->toArray());

        $config->set('test', 'value');
        $objValue = new \stdClass();
        $objValue->someProp = 123;
        $config->set('test_object', $objValue);

        $configClone = clone $config;

        self::assertEquals($config, $configClone);
        self::assertNotSame($objValue, $configClone->get('test_object'));
    }

    public function testCustomAttribute(): void
    {
        $attrName = 'test';

        $config = new ActionFieldConfig();
        self::assertFalse($config->has($attrName));
        self::assertNull($config->get($attrName));
        self::assertSame([], $config->keys());

        $config->set($attrName, null);
        self::assertFalse($config->has($attrName));
        self::assertNull($config->get($attrName));
        self::assertEquals([], $config->toArray());
        self::assertSame([], $config->keys());

        $config->set($attrName, false);
        self::assertTrue($config->has($attrName));
        self::assertFalse($config->get($attrName));
        self::assertEquals([$attrName => false], $config->toArray());
        self::assertEquals([$attrName], $config->keys());

        $config->remove($attrName);
        self::assertFalse($config->has($attrName));
        self::assertNull($config->get($attrName));
        self::assertSame([], $config->toArray());
        self::assertSame([], $config->keys());
    }

    public function testExcluded(): void
    {
        $config = new ActionFieldConfig();
        self::assertFalse($config->hasExcluded());
        self::assertFalse($config->isExcluded());

        $config->setExcluded();
        self::assertTrue($config->hasExcluded());
        self::assertTrue($config->isExcluded());
        self::assertEquals(['exclude' => true], $config->toArray());

        $config->setExcluded(false);
        self::assertTrue($config->hasExcluded());
        self::assertFalse($config->isExcluded());
        self::assertEquals([], $config->toArray());
    }

    public function testPropertyPath(): void
    {
        $config = new ActionFieldConfig();
        self::assertFalse($config->hasPropertyPath());
        self::assertNull($config->getPropertyPath());
        self::assertEquals('default', $config->getPropertyPath('default'));

        $config->setPropertyPath('path');
        self::assertTrue($config->hasPropertyPath());
        self::assertEquals('path', $config->getPropertyPath());
        self::assertEquals('path', $config->getPropertyPath('default'));
        self::assertEquals(['property_path' => 'path'], $config->toArray());

        $config->setPropertyPath(null);
        self::assertFalse($config->hasPropertyPath());
        self::assertNull($config->getPropertyPath());
        self::assertEquals([], $config->toArray());

        $config->setPropertyPath('path');
        $config->setPropertyPath('');
        self::assertFalse($config->hasPropertyPath());
        self::assertNull($config->getPropertyPath());
        self::assertEquals('default', $config->getPropertyPath('default'));
        self::assertEquals([], $config->toArray());
    }

    public function testDirection(): void
    {
        $config = new ActionFieldConfig();
        self::assertFalse($config->hasDirection());
        self::assertTrue($config->isInput());
        self::assertTrue($config->isOutput());

        $config->setDirection('input-only');
        self::assertTrue($config->hasDirection());
        self::assertTrue($config->isInput());
        self::assertFalse($config->isOutput());
        self::assertEquals(['direction' => 'input-only'], $config->toArray());

        $config->setDirection('output-only');
        self::assertTrue($config->hasDirection());
        self::assertFalse($config->isInput());
        self::assertTrue($config->isOutput());
        self::assertEquals(['direction' => 'output-only'], $config->toArray());

        $config->setDirection('bidirectional');
        self::assertTrue($config->hasDirection());
        self::assertTrue($config->isInput());
        self::assertTrue($config->isOutput());
        self::assertEquals(['direction' => 'bidirectional'], $config->toArray());

        $config->setDirection(null);
        self::assertFalse($config->hasDirection());
        self::assertTrue($config->isInput());
        self::assertTrue($config->isOutput());
        self::assertEquals([], $config->toArray());
    }

    public function testSetInvalidDirection(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'The possible values for the direction are "input-only", "output-only" or "bidirectional".'
        );

        $config = new ActionFieldConfig();

        $config->setDirection('another');
    }

    public function testFormType(): void
    {
        $config = new ActionFieldConfig();
        self::assertNull($config->getFormType());

        $config->setFormType('test');
        self::assertEquals('test', $config->getFormType());
        self::assertEquals(['form_type' => 'test'], $config->toArray());

        $config->setFormType(null);
        self::assertNull($config->getFormType());
        self::assertEquals([], $config->toArray());
    }

    public function testFormOptions(): void
    {
        $config = new ActionFieldConfig();
        self::assertNull($config->getFormOptions());
        self::assertNull($config->getFormOption('key'));
        self::assertSame('', $config->getFormOption('key', ''));

        $config->setFormOptions(['key' => 'val']);
        self::assertEquals(['key' => 'val'], $config->getFormOptions());
        self::assertEquals(['form_options' => ['key' => 'val']], $config->toArray());
        self::assertSame('val', $config->getFormOption('key'));
        self::assertSame('val', $config->getFormOption('key', ''));

        $config->setFormOptions([]);
        self::assertNull($config->getFormOptions());
        self::assertEquals([], $config->toArray());
        self::assertNull($config->getFormOption('key'));
        self::assertSame('', $config->getFormOption('key', ''));

        $config->setFormOptions(null);
        self::assertNull($config->getFormOptions());
        self::assertEquals([], $config->toArray());
        self::assertNull($config->getFormOption('key'));
        self::assertSame('', $config->getFormOption('key', ''));
    }

    public function testSetFormOption(): void
    {
        $config = new ActionFieldConfig();

        $config->setFormOption('option1', 'value1');
        $config->setFormOption('option2', 'value2');
        self::assertEquals(
            ['option1' => 'value1', 'option2' => 'value2'],
            $config->getFormOptions()
        );

        $config->setFormOption('option1', 'newValue');
        self::assertEquals(
            ['option1' => 'newValue', 'option2' => 'value2'],
            $config->getFormOptions()
        );
    }

    public function testFormConstraints(): void
    {
        $config = new ActionFieldConfig();

        self::assertNull($config->getFormOptions());
        self::assertNull($config->getFormConstraints());

        $config->addFormConstraint(new NotNull());
        self::assertEquals(['constraints' => [new NotNull()]], $config->getFormOptions());
        self::assertEquals([new NotNull()], $config->getFormConstraints());

        $config->addFormConstraint(new NotBlank());
        self::assertEquals(['constraints' => [new NotNull(), new NotBlank()]], $config->getFormOptions());
        self::assertEquals([new NotNull(), new NotBlank()], $config->getFormConstraints());
    }

    public function testRemoveFormConstraint(): void
    {
        $config = new ActionFieldConfig();

        self::assertNull($config->getFormOptions());
        self::assertNull($config->getFormConstraints());

        $config->removeFormConstraint(NotNull::class);
        self::assertNull($config->getFormConstraints());

        $config->setFormOption(
            'constraints',
            [
                new NotNull(),
                new NotBlank(),
                [NotNull::class => ['message' => 'test']]
            ]
        );

        $config->removeFormConstraint(NotNull::class);
        self::assertEquals(['constraints' => [new NotBlank()]], $config->getFormOptions());

        $config->removeFormConstraint(NotBlank::class);
        self::assertNull($config->getFormOptions());
    }

    public function testPostProcessor(): void
    {
        $config = new ActionFieldConfig();
        self::assertFalse($config->hasPostProcessor());
        self::assertNull($config->getPostProcessor());

        $config->setPostProcessor('test');
        self::assertTrue($config->hasPostProcessor());
        self::assertEquals('test', $config->getPostProcessor());
        self::assertEquals(['post_processor' => 'test'], $config->toArray());

        $config->setPostProcessor(null);
        self::assertTrue($config->hasPostProcessor());
        self::assertNull($config->getPostProcessor());
        self::assertEquals(['post_processor' => null], $config->toArray());

        $config->removePostProcessor();
        self::assertFalse($config->hasPostProcessor());
        self::assertNull($config->getPostProcessor());
        self::assertEquals([], $config->toArray());
    }

    public function testPostProcessorOptions(): void
    {
        $config = new ActionFieldConfig();
        self::assertNull($config->getPostProcessorOptions());

        $config->setPostProcessorOptions(['key' => 'val']);
        self::assertEquals(['key' => 'val'], $config->getPostProcessorOptions());
        self::assertEquals(['post_processor_options' => ['key' => 'val']], $config->toArray());

        $config->setPostProcessorOptions(null);
        self::assertNull($config->getPostProcessorOptions());
        self::assertEquals([], $config->toArray());
    }
}
