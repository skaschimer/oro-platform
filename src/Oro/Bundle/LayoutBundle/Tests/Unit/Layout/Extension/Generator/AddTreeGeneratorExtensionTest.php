<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Extension\Generator;

use Oro\Bundle\LayoutBundle\Layout\Extension\Generator\AddTreeGeneratorExtension;
use Oro\Component\Layout\Exception\SyntaxException;
use Oro\Component\Layout\Loader\Generator\GeneratorData;
use Oro\Component\Layout\Loader\Visitor\VisitorCollection;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

class AddTreeGeneratorExtensionTest extends TestCase
{
    private AddTreeGeneratorExtension $extension;

    #[\Override]
    protected function setUp(): void
    {
        $this->extension = new AddTreeGeneratorExtension();
    }

    /**
     * @dataProvider prepareDataProvider
     */
    public function testPrepare(array $source, array $expectedSource): void
    {
        $data = new GeneratorData($source);

        $visitorCollection = new VisitorCollection();
        $this->extension->prepare($data, $visitorCollection);
        $this->assertEmpty($visitorCollection);
        $this->assertNull($data->getFilename());
        $this->assertEquals($expectedSource, $data->getSource());
    }

    public function prepareDataProvider(): array
    {
        $source = Yaml::parse(file_get_contents(__DIR__.'/data/layout.yml'))['layouts'];
        $sourceMultiple = Yaml::parse(file_get_contents(__DIR__.'/data/layout_multiple_add_tree.yml'))['layouts'];

        return [
            'tree' => [
                'source' => $source,
                'expectedSource' => [
                    'actions' => [
                        ['@add' => ['meta', 'header', 'text']],
                        [
                            '@add' => [
                                'id' => 'header',
                                'blockType' => 'header',
                                'parentId' => 'root',
                            ],
                            '__path' => 'actions.1',
                        ],
                        [
                            '@add' => ['css', 'header', 'style'],
                            '__path' => 'actions.1',
                        ],
                        [
                            '@add' => [
                                'id' => 'body',
                                'blockType' => 'content',
                                'parentId' => 'root',
                                'options' => ['test' => true],
                            ],
                            '__path' => 'actions.1',
                        ],
                        [
                            '@add' => [
                                'id' => 'footer',
                                'blockType' => 'header',
                                'parentId' => 'root',
                            ],
                            '__path' => 'actions.1',
                        ],
                        [
                            '@add' => [
                                'id' => 'copyrights',
                                'blockType' => 'block',
                                'parentId' => 'footer',
                            ],
                            '__path' => 'actions.1',
                        ],
                    ]
                ],
            ],
            'several_trees' => [
                'source' => $sourceMultiple,
                'expectedSource' => [
                    'actions' => [
                        ['@add' => ['meta', 'header', 'text']],
                        [
                            '@add' => [
                                'id' => 'header',
                                'blockType' => 'header',
                                'parentId' => 'root',
                            ],
                            '__path' => 'actions.1',
                        ],
                        [
                            '@add' => ['css', 'header', 'style'],
                            '__path' => 'actions.1',
                        ],
                        [
                            '@add' => [
                                'id' => 'body',
                                'blockType' => 'content',
                                'parentId' => 'root',
                                'options' => ['test' => true],
                            ],
                            '__path' => 'actions.2',
                        ],
                        [
                            '@add' => [
                                'id' => 'footer',
                                'blockType' => 'header',
                                'parentId' => 'root',
                            ],
                            '__path' => 'actions.2',
                        ],
                        [
                            '@add' => [
                                'id' => 'copyrights',
                                'blockType' => 'block',
                                'parentId' => 'footer',
                            ],
                            '__path' => 'actions.2',
                        ],
                    ]
                ],
            ],
        ];
    }

    /**
     * @dataProvider prepareExceptionDataProvider
     */
    public function testPrepareExceptions(array $source, string $message): void
    {
        $this->expectException(SyntaxException::class);
        $this->expectExceptionMessage($message);
        $visitorCollection = new VisitorCollection();
        $this->extension->prepare(new GeneratorData($source), $visitorCollection);
    }

    public function prepareExceptionDataProvider(): array
    {
        return [
            '@addTree with invalid structure' => [
                'source' => [
                    'actions' => [
                        ['@addTree' => null]
                    ]
                ],
                'message' => 'expected array with keys "items" and "tree" at "actions.0"',
            ],
            '@addTree item not found in "items" list' => [
                'source' => [
                    'actions' => [
                        ['@addTree' => ['items' => [], 'tree' => ['root' => ['head' => null]]]]
                    ]
                ],
                'message' => 'invalid tree definition. Item with id "head" not found in items list at "actions.0"'
            ],
            '@addTree several roots' => [
                'source' => [
                    'actions' => [
                        ['@addTree' => ['items' => [], 'tree' => ['root' => [], 'head' => []]]]
                    ]
                ],
                'message' => 'tree expects only one child at "actions.0"'
            ],
            '@addTree no root' => [
                'source' => [
                    'actions' => [
                        ['@addTree' => ['items' => [], 'tree' => []]]
                    ]
                ],
                'message' => 'tree expects only one child at "actions.0"'
            ],
        ];
    }
}
