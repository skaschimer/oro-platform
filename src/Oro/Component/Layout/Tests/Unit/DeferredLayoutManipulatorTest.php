<?php

namespace Oro\Component\Layout\Tests\Unit;

use Oro\Component\Layout\Block\Type\ContainerType;
use Oro\Component\Layout\CallbackLayoutUpdate;
use Oro\Component\Layout\Exception\LogicException;
use Oro\Component\Layout\Extension\PreloadedExtension;
use Oro\Component\Layout\LayoutItemInterface;
use Oro\Component\Layout\LayoutManipulatorInterface;
use Oro\Component\Layout\Tests\Unit\Fixtures\Layout\Block\Type\HeaderType;

/**
 * This class contains unit tests which are NOT RELATED to ALIASES and CHANGE COUNTERS
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class DeferredLayoutManipulatorTest extends DeferredLayoutManipulatorTestCase
{
    public function testClear(): void
    {
        // prepare data
        $this->layoutManipulator
            ->add('root', null, 'root')
            ->add('header', 'root', 'header')
            ->remove('header');

        // do test
        $this->assertSame($this->layoutManipulator, $this->layoutManipulator->clear());
        $this->layoutManipulator->applyChanges($this->context, true);
        $this->assertTrue($this->rawLayoutBuilder->isEmpty());
        $this->assertSame(0, $this->layoutManipulator->getNumberOfAddedItems());
    }

    public function testSimpleLayout(): void
    {
        $this->layoutManipulator
            ->add('root', null, 'root')
            ->add('header', 'root', 'header')
            ->add('logo', 'header', 'logo', ['title' => 'test']);

        $view = $this->getLayoutView();

        $this->assertBlockView(
            [ // root
                'vars'     => ['id' => 'root'],
                'children' => [
                    [ // header
                        'vars'     => ['id' => 'header'],
                        'children' => [
                            [ // logo
                                'vars' => ['id' => 'logo', 'title' => 'test']
                            ]
                        ]
                    ]
                ]
            ],
            $view
        );
    }

    public function testSimpleLayoutWhenSomeBlocksCreatedDirectly(): void
    {
        $this->layoutManipulator
            ->add('root', null, 'root')
            ->add('header', 'root', new HeaderType())
            ->add('logo', 'header', 'logo', ['title' => 'test']);

        $view = $this->getLayoutView();

        $this->assertBlockView(
            [ // root
                'vars'     => ['id' => 'root'],
                'children' => [
                    [ // header
                        'vars'     => ['id' => 'header'],
                        'children' => [
                            [ // logo
                                'vars' => ['id' => 'logo', 'title' => 'test']
                            ]
                        ]
                    ]
                ]
            ],
            $view
        );
    }

    public function testAddWhenRootIsAddedAtTheEnd(): void
    {
        $this->layoutManipulator
            ->add('logo', 'header', 'logo', ['title' => 'test'])
            ->add('header', 'root', 'header')
            ->add('root', null, 'root');

        $view = $this->getLayoutView();

        $this->assertBlockView(
            [ // root
                'vars'     => ['id' => 'root'],
                'children' => [
                    [ // header
                        'vars'     => ['id' => 'header'],
                        'children' => [
                            [ // logo
                                'vars' => ['id' => 'logo', 'title' => 'test']
                            ],
                        ]
                    ]
                ]
            ],
            $view
        );
    }

    public function testAddTwoChildren(): void
    {
        $this->layoutManipulator
            ->add('root', null, 'root')
            ->add('header', 'root', 'header')
            ->add('logo1', 'header', 'logo', ['title' => 'logo1'])
            ->add('logo2', 'header', 'logo', ['title' => 'logo2']);

        $view = $this->getLayoutView();

        $this->assertBlockView(
            [ // root
                'vars'     => ['id' => 'root'],
                'children' => [
                    [ // header
                        'vars'     => ['id' => 'header'],
                        'children' => [
                            [ // logo1
                                'vars' => ['id' => 'logo1', 'title' => 'logo1']
                            ],
                            [ // logo2
                                'vars' => ['id' => 'logo2', 'title' => 'logo2']
                            ]
                        ]
                    ]
                ]
            ],
            $view
        );
    }

    /** It is expected that children are added in the same order as they are registered */
    public function testAddTwoChildrenButTheFirstChildIsAddedBeforeContainer(): void
    {
        $this->layoutManipulator
            ->add('root', null, 'root')
            ->add('logo1', 'header', 'logo', ['title' => 'logo1'])
            ->add('header', 'root', 'header')
            ->add('logo2', 'header', 'logo', ['title' => 'logo2']);

        $view = $this->getLayoutView();

        $this->assertBlockView(
            [ // root
                'vars'     => ['id' => 'root'],
                'children' => [
                    [ // header
                        'vars'     => ['id' => 'header'],
                        'children' => [
                            [ // logo1
                                'vars' => ['id' => 'logo1', 'title' => 'logo1']
                            ],
                            [ // logo2
                                'vars' => ['id' => 'logo2', 'title' => 'logo2']
                            ]
                        ]
                    ]
                ]
            ],
            $view
        );
    }

    public function testRemoveBeforeAdd(): void
    {
        $this->layoutManipulator
            ->add('root', null, 'root')
            ->remove('header')
            ->add('header', 'root', 'header');

        $view = $this->getLayoutView();

        $this->assertBlockView(
            [ // root
                'vars' => ['id' => 'root'],
            ],
            $view
        );
    }

    public function testRemoveAfterAdd(): void
    {
        $this->layoutManipulator
            ->add('root', null, 'root')
            ->add('header', 'root', 'header')
            ->add('logo', 'header', 'logo', ['title' => 'test'])
            ->remove('header');

        $view = $this->getLayoutView();

        $this->assertBlockView(
            [ // root
                'vars' => ['id' => 'root'],
            ],
            $view
        );
    }

    public function testAddToRemovedItem(): void
    {
        $this->layoutManipulator
            ->add('root', null, 'root')
            ->add('header', 'root', 'header')
            ->remove('header')
            ->add('logo', 'header', 'logo', ['title' => 'test']);

        $view = $this->getLayoutView();

        $this->assertBlockView(
            [ // root
                'vars' => ['id' => 'root'],
            ],
            $view
        );
    }

    public function testRemoveNotExistItem(): void
    {
        $this->layoutManipulator
            ->add('root', null, 'root')
            ->remove('header');

        $view = $this->getLayoutView();

        $this->assertBlockView(
            [ // root
                'vars' => ['id' => 'root'],
            ],
            $view
        );
    }

    public function testRemoveAlreadyRemovedItem(): void
    {
        $this->layoutManipulator
            ->add('root', null, 'root')
            ->add('header', 'root', 'header')
            ->remove('header')
            ->remove('header');

        $view = $this->getLayoutView();

        $this->assertBlockView(
            [ // root
                'vars' => ['id' => 'root'],
            ],
            $view
        );
    }

    public function testMoveChildAndThenRemoveParent(): void
    {
        $this->layoutManipulator
            ->add('root', null, 'root')
            ->add('header1', 'root', 'header')
            ->add('header2', 'root', 'header')
            ->add('logo', 'header1', 'logo', ['title' => 'logo'])
            ->move('logo', 'header2');
        $this->layoutManipulator->applyChanges($this->context);
        $this->layoutManipulator
            ->remove('header1');

        $view = $this->getLayoutView();

        $this->assertBlockView(
            [ // root
                'vars'     => ['id' => 'root'],
                'children' => [
                    [ // header2
                        'vars'     => ['id' => 'header2'],
                        'children' => [
                            [ // logo
                                'vars' => ['id' => 'logo', 'title' => 'logo']
                            ]
                        ]
                    ]
                ]
            ],
            $view
        );
    }

    public function testMoveChildOfRemovedParent(): void
    {
        $this->layoutManipulator
            ->add('root', null, 'root')
            ->add('header1', 'root', 'header')
            ->add('header2', 'root', 'header')
            ->add('logo', 'header1', 'logo', ['title' => 'logo'])
            ->remove('header1');
        $this->layoutManipulator->applyChanges($this->context);
        $this->layoutManipulator
            ->move('logo', 'header2');

        $view = $this->getLayoutView();

        $this->assertBlockView(
            [ // root
                'vars'     => ['id' => 'root'],
                'children' => [
                    [ // header2
                        'vars'     => ['id' => 'header2'],
                        'children' => [
                            [ // logo
                                'vars' => ['id' => 'logo', 'title' => 'logo']
                            ]
                        ]
                    ]
                ]
            ],
            $view
        );
    }

    public function testReplaceItem(): void
    {
        $this->layoutManipulator
            ->add('root', null, 'root')
            ->add('header', 'root', 'header')
            ->add('logo', 'header', 'logo', ['title' => 'logo'])
            ->remove('logo')
            ->add('logo', 'header', 'logo', ['title' => 'new_logo']);

        $view = $this->getLayoutView();

        $this->assertBlockView(
            [ // root
                'vars'     => ['id' => 'root'],
                'children' => [
                    [ // header
                        'vars'     => ['id' => 'header'],
                        'children' => [
                            [ // logo
                                'vars' => ['id' => 'logo', 'title' => 'new_logo']
                            ]
                        ]
                    ]
                ]
            ],
            $view
        );
    }

    public function testReplaceItemWhenNewItemIsAddedInAnotherBatch(): void
    {
        $this->layoutManipulator
            ->add('root', null, 'root')
            ->add('header', 'root', 'header')
            ->add('logo', 'header', 'logo', ['title' => 'logo'])
            ->remove('logo');
        $this->layoutManipulator->applyChanges($this->context);
        $this->layoutManipulator
            ->add('logo', 'header', 'logo', ['title' => 'new_logo']);

        $view = $this->getLayoutView();

        $this->assertBlockView(
            [ // root
                'vars'     => ['id' => 'root'],
                'children' => [
                    [ // header
                        'vars'     => ['id' => 'header'],
                        'children' => [
                            [ // logo
                                'vars' => ['id' => 'logo', 'title' => 'new_logo']
                            ]
                        ]
                    ]
                ]
            ],
            $view
        );
    }

    public function testReplaceItemAfterRemoveParent(): void
    {
        $this->layoutManipulator
            ->add('root', null, 'root')
            ->add('header', 'root', 'header')
            ->add('logo', 'header', 'logo', ['title' => 'logo'])
            ->remove('header')
            ->add('logo', 'root', 'logo', ['title' => 'new_logo']);

        $view = $this->getLayoutView();

        $this->assertBlockView(
            [ // root
                'vars'     => ['id' => 'root'],
                'children' => [
                    [ // logo
                        'vars' => ['id' => 'logo', 'title' => 'new_logo']
                    ]
                ]
            ],
            $view
        );
    }

    public function testDuplicateAdd(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'Cannot add "logo" item to the layout. ParentId: root. BlockType: logo. SiblingId: .'
            . ' Reason: The "logo" item already exists. Remove existing item before add the new item with the same id.'
        );

        $this->layoutManipulator
            ->add('root', null, 'root')
            ->add('header', 'root', 'header')
            ->add('logo', 'header', 'logo')
            ->add('logo', 'root', 'logo');

        $this->getLayoutView();
    }

    public function testAddWithSibling(): void
    {
        $this->layoutManipulator
            ->add('root', null, 'root')
            ->add('header', 'root', 'header')
            ->add('logo2', 'header', 'logo', [], 'logo3')
            ->add('logo1', 'header', 'logo', [])
            ->add('logo3', 'header', 'logo', [], 'logo1', true);

        $view = $this->getLayoutView();

        $this->assertBlockView(
            [ // root
                'vars'     => ['id' => 'root'],
                'children' => [
                    [ // header
                        'vars'     => ['id' => 'header'],
                        'children' => [
                            [ // logo3
                                'vars' => ['id' => 'logo3', 'title' => '']
                            ],
                            [ // logo2
                                'vars' => ['id' => 'logo2', 'title' => '']
                            ],
                            [ // logo1
                                'vars' => ['id' => 'logo1', 'title' => '']
                            ]
                        ]
                    ]
                ]
            ],
            $view
        );
    }

    public function testAddWithUnknownSibling(): void
    {
        $this->layoutManipulator
            ->add('root', null, 'root')
            ->add('header', 'root', 'header')
            ->add('logo2', 'header', 'logo', [], 'unknown1')
            ->add('logo1', 'header', 'logo', [])
            ->add('logo3', 'header', 'logo', [], 'unknown2', true);

        $view = $this->getLayoutView();

        $this->assertBlockView(
            [ // root
                'vars'     => ['id' => 'root'],
                'children' => [
                    [ // header
                        'vars'     => ['id' => 'header'],
                        'children' => [
                            [ // logo3
                                'vars' => ['id' => 'logo3', 'title' => '']
                            ],
                            [ // logo1
                                'vars' => ['id' => 'logo1', 'title' => '']
                            ],
                            [ // logo2
                                'vars' => ['id' => 'logo2', 'title' => '']
                            ]
                        ]
                    ]
                ]
            ],
            $view
        );
    }

    public function testAddWithSiblingAndMoveToUnknownSiblingAfterAdd(): void
    {
        $this->layoutManipulator
            ->add('root', null, 'root')
            ->add('header1', 'root', 'header')
            ->add('header2', 'root', 'header')
            ->add('logo1', 'header1', 'logo', [])
            ->add('logo2', 'header2', 'logo', [])
            ->add('logo3', 'header1', 'logo', [], 'logo2')
            ->move('logo2', 'header1', 'unknown', true);

        $view = $this->getLayoutView();

        $this->assertBlockView(
            [ // root
                'vars'     => ['id' => 'root'],
                'children' => [
                    [ // header1
                        'vars'     => ['id' => 'header1'],
                        'children' => [
                            [ // logo2
                                'vars' => ['id' => 'logo2', 'title' => '']
                            ],
                            [ // logo1
                                'vars' => ['id' => 'logo1', 'title' => '']
                            ],
                            [ // logo3
                                'vars' => ['id' => 'logo3', 'title' => '']
                            ]
                        ]
                    ],
                    [ // header2
                        'vars'     => ['id' => 'header2'],
                        'children' => []
                    ]
                ]
            ],
            $view
        );
    }

    public function testAddWithSiblingAndMoveToUnknownSiblingBeforeAdd(): void
    {
        $this->layoutManipulator
            ->add('root', null, 'root')
            ->add('header1', 'root', 'header')
            ->add('header2', 'root', 'header')
            ->add('logo1', 'header1', 'logo', [])
            ->add('logo2', 'header2', 'logo', [])
            ->move('logo2', 'header1', 'unknown', true)
            ->add('logo3', 'header1', 'logo', [], 'logo2');

        $view = $this->getLayoutView();

        $this->assertBlockView(
            [ // root
                'vars'     => ['id' => 'root'],
                'children' => [
                    [ // header1
                        'vars'     => ['id' => 'header1'],
                        'children' => [
                            [ // logo2
                                'vars' => ['id' => 'logo2', 'title' => '']
                            ],
                            [ // logo3
                                'vars' => ['id' => 'logo3', 'title' => '']
                            ],
                            [ // logo1
                                'vars' => ['id' => 'logo1', 'title' => '']
                            ]
                        ]
                    ],
                    [ // header2
                        'vars'     => ['id' => 'header2'],
                        'children' => []
                    ]
                ]
            ],
            $view
        );
    }

    public function testSetOption(): void
    {
        $this->layoutManipulator
            ->add('root', null, 'root')
            ->setOption('logo', 'title', 'test1')
            ->add('header', 'root', 'header')
            ->add('logo', 'header', 'logo', ['title' => 'test']);

        $view = $this->getLayoutView();

        $this->assertBlockView(
            [ // root
                'vars'     => ['id' => 'root'],
                'children' => [
                    [ // header
                        'vars'     => ['id' => 'header'],
                        'children' => [
                            [ // logo
                                'vars' => ['id' => 'logo', 'title' => 'test1']
                            ]
                        ]
                    ]
                ]
            ],
            $view
        );
    }

    public function testSetOptionByPath(): void
    {
        $this->layoutManipulator
            ->add('root', null, 'root')
            ->setOption('logo', 'attr.class', 'test_class')
            ->add('header', 'root', 'header')
            ->add('logo', 'header', 'logo');

        $view = $this->getLayoutView();

        $this->assertBlockView(
            [ // root
                'vars'     => ['id' => 'root'],
                'children' => [
                    [ // header
                        'vars'     => ['id' => 'header'],
                        'children' => [
                            [ // logo
                                'vars' => ['id' => 'logo', 'title' => '', 'attr' => ['class' => 'test_class']]
                            ]
                        ]
                    ]
                ]
            ],
            $view
        );
    }

    public function testAppendOption(): void
    {
        $this->layoutManipulator
            ->add('root', null, 'root')
            ->appendOption('logo', 'attr.class', 'test_class2')
            ->add('header', 'root', 'header')
            ->add('logo', 'header', 'logo', ['attr' => ['class' => 'test_class1']]);

        $view = $this->getLayoutView();

        $this->assertBlockView(
            [ // root
                'vars'     => ['id' => 'root'],
                'children' => [
                    [ // header
                        'vars'     => ['id' => 'header'],
                        'children' => [
                            [ // logo
                                'vars' => [
                                    'id'    => 'logo',
                                    'title' => '',
                                    'attr'  => ['class' => 'test_class1 test_class2']
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            $view
        );
    }

    public function testAppendOptionWhenNoPrevOption(): void
    {
        $this->layoutManipulator
            ->add('root', null, 'root')
            ->appendOption('logo', 'attr.class', 'test_class2')
            ->add('header', 'root', 'header')
            ->add('logo', 'header', 'logo');

        $view = $this->getLayoutView();

        $this->assertBlockView(
            [ // root
                'vars'     => ['id' => 'root'],
                'children' => [
                    [ // header
                        'vars'     => ['id' => 'header'],
                        'children' => [
                            [ // logo
                                'vars' => [
                                    'id'    => 'logo',
                                    'title' => '',
                                    'attr'  => ['class' => 'test_class2']
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            $view
        );
    }

    public function testSubtractOption(): void
    {
        $this->layoutManipulator
            ->add('root', null, 'root')
            ->subtractOption('logo', 'attr.class', 'test_class2')
            ->add('header', 'root', 'header')
            ->add('logo', 'header', 'logo', ['attr' => ['class' => 'test_class1 test_class2']]);

        $view = $this->getLayoutView();

        $this->assertBlockView(
            [ // root
                'vars'     => ['id' => 'root'],
                'children' => [
                    [ // header
                        'vars'     => ['id' => 'header'],
                        'children' => [
                            [ // logo
                                'vars' => [
                                    'id'    => 'logo',
                                    'title' => '',
                                    'attr'  => ['class' => 'test_class1']
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            $view
        );
    }

    public function testSubtractOptionWhenNoPrevOption(): void
    {
        $this->layoutManipulator
            ->add('root', null, 'root')
            ->subtractOption('logo', 'attr.class', 'test_class2')
            ->add('header', 'root', 'header')
            ->add('logo', 'header', 'logo');

        $view = $this->getLayoutView();

        $this->assertBlockView(
            [ // root
                'vars'     => ['id' => 'root'],
                'children' => [
                    [ // header
                        'vars'     => ['id' => 'header'],
                        'children' => [
                            [ // logo
                                'vars' => [
                                    'id'    => 'logo',
                                    'title' => ''
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            $view
        );
    }

    public function testReplaceOption(): void
    {
        $this->layoutManipulator
            ->add('root', null, 'root')
            ->replaceOption('logo', 'attr.class', 'test_class1', 'new_class1')
            ->add('header', 'root', 'header')
            ->add('logo', 'header', 'logo', ['attr' => ['class' => 'test_class1 test_class2']]);

        $view = $this->getLayoutView();

        $this->assertBlockView(
            [ // root
                'vars'     => ['id' => 'root'],
                'children' => [
                    [ // header
                        'vars'     => ['id' => 'header'],
                        'children' => [
                            [ // logo
                                'vars' => [
                                    'id'    => 'logo',
                                    'title' => '',
                                    'attr'  => ['class' => 'new_class1 test_class2']
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            $view
        );
    }

    public function testReplaceOptionForUnknownValue(): void
    {
        $this->layoutManipulator
            ->add('root', null, 'root')
            ->replaceOption('logo', 'attr.class', 'unknown_class', 'new_class')
            ->add('header', 'root', 'header')
            ->add('logo', 'header', 'logo', ['attr' => ['class' => 'test_class1 test_class2']]);

        $view = $this->getLayoutView();

        $this->assertBlockView(
            [ // root
                'vars'     => ['id' => 'root'],
                'children' => [
                    [ // header
                        'vars'     => ['id' => 'header'],
                        'children' => [
                            [ // logo
                                'vars' => [
                                    'id'    => 'logo',
                                    'title' => '',
                                    'attr'  => ['class' => 'test_class1 test_class2']
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            $view
        );
    }

    public function testRemoveOption(): void
    {
        $this->layoutManipulator
            ->add('root', null, 'root')
            ->removeOption('logo', 'title')
            ->add('header', 'root', 'header')
            ->add('logo', 'header', 'logo', ['title' => 'test']);

        $view = $this->getLayoutView();

        $this->assertBlockView(
            [ // root
                'vars'     => ['id' => 'root'],
                'children' => [
                    [ // header
                        'vars'     =>  ['id' => 'header'],
                        'children' => [
                            [ // logo
                                'vars' => ['id' => 'logo', 'title' => '']
                            ]
                        ]
                    ]
                ]
            ],
            $view
        );
    }

    public function testRemoveOptionByPath(): void
    {
        $this->layoutManipulator
            ->add('root', null, 'root')
            ->removeOption('logo', 'attr.class')
            ->add('header', 'root', 'header')
            ->add('logo', 'header', 'logo', ['attr' => ['class' => 'test_class']]);

        $view = $this->getLayoutView();

        $this->assertBlockView(
            [ // root
                'vars'     => ['id' => 'root'],
                'children' => [
                    [ // header
                        'vars'     => ['id' => 'header'],
                        'children' => [
                            [ // logo
                                'vars' => ['id' => 'logo', 'title' => '', 'attr' => []]
                            ]
                        ]
                    ]
                ]
            ],
            $view
        );
    }

    public function testSetOptionForRemovedItem(): void
    {
        $this->layoutManipulator
            ->add('root', null, 'root')
            ->add('header', 'root', 'header')
            ->add('logo', 'header', 'logo', ['title' => 'test'])
            ->remove('header')
            ->setOption('logo', 'title', 'test1');

        $view = $this->getLayoutView();

        $this->assertBlockView(
            [ // root
                'vars' => ['id' => 'root'],
            ],
            $view
        );
    }

    public function testAppendOptionForRemovedItem(): void
    {
        $this->layoutManipulator
            ->add('root', null, 'root')
            ->add('header', 'root', 'header')
            ->add('logo', 'header', 'logo', ['attr' => ['class' => 'test_class1']])
            ->remove('header')
            ->appendOption('logo', 'attr.class', 'test_class2');

        $view = $this->getLayoutView();

        $this->assertBlockView(
            [ // root
                'vars' => ['id' => 'root'],
            ],
            $view
        );
    }

    public function testSubtractOptionForRemovedItem(): void
    {
        $this->layoutManipulator
            ->add('root', null, 'root')
            ->add('header', 'root', 'header')
            ->add('logo', 'header', 'logo', ['attr' => ['class' => 'test_class1 test_class2']])
            ->remove('header')
            ->subtractOption('logo', 'attr.class', 'test_class2');

        $view = $this->getLayoutView();

        $this->assertBlockView(
            [ // root
                'vars' => ['id' => 'root'],
            ],
            $view
        );
    }

    public function testReplaceOptionForRemovedItem(): void
    {
        $this->layoutManipulator
            ->add('root', null, 'root')
            ->add('header', 'root', 'header')
            ->add('logo', 'header', 'logo', ['attr' => ['class' => 'test_class1 test_class2']])
            ->remove('header')
            ->replaceOption('logo', 'attr.class', 'test_class1', 'new_class1');

        $view = $this->getLayoutView();

        $this->assertBlockView(
            [ // root
                'vars' => ['id' => 'root'],
            ],
            $view
        );
    }

    public function testRemoveOptionForRemovedItem(): void
    {
        $this->layoutManipulator
            ->add('root', null, 'root')
            ->add('header', 'root', 'header')
            ->add('logo', 'header', 'logo', ['title' => 'test'])
            ->remove('header')
            ->removeOption('logo', 'title');

        $view = $this->getLayoutView();

        $this->assertBlockView(
            [ // root
                'vars' => ['id' => 'root'],
            ],
            $view
        );
    }

    public function testOptionManipulations(): void
    {
        $this->layoutManipulator
            ->add('root', null, 'root')
            ->add('header', 'root', 'header')
            ->add('logo', 'header', 'logo', ['attr' => ['class' => 'test_class1']])
            ->appendOption('logo', 'attr.class', 'test_class2')
            ->subtractOption('logo', 'attr.class', 'test_class1')
            ->setOption('logo', 'attr.class', 'new_class1')
            ->appendOption('logo', 'attr.class', 'new_class2')
            ->subtractOption('logo', 'attr.class', 'new_class1')
            ->replaceOption('logo', 'attr.class', 'new_class2', 'replaced_class2');

        $view = $this->getLayoutView();

        $this->assertBlockView(
            [ // root
                'vars'     => ['id' => 'root'],
                'children' => [
                    [ // header
                        'vars'     => ['id' => 'header'],
                        'children' => [
                            [ // logo
                                'vars' => [
                                    'id'    => 'logo',
                                    'title' => '',
                                    'attr'  => ['class' => 'replaced_class2']
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            $view
        );
    }

    public function testSubtractAndThenAppendOption(): void
    {
        $this->layoutManipulator
            ->add('root', null, 'root')
            ->add('header', 'root', 'header')
            ->add('logo', 'header', 'logo', ['attr' => ['class' => 'test_class1']])
            ->subtractOption('logo', 'attr.class', 'test_class2')
            ->appendOption('logo', 'attr.class', 'test_class2');

        $view = $this->getLayoutView();

        $this->assertBlockView(
            [ // root
                'vars'     => ['id' => 'root'],
                'children' => [
                    [ // header
                        'vars'     => ['id' => 'header'],
                        'children' => [
                            [ // logo
                                'vars' => [
                                    'id'    => 'logo',
                                    'title' => '',
                                    'attr'  => ['class' => 'test_class1']
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            $view
        );
    }

    public function testChangeBlockType(): void
    {
        $this->layoutManipulator
            ->add('root', null, 'root')
            ->changeBlockType(
                'header',
                'logo',
                function (array $options) {
                    $options['title'] = 'test';

                    return $options;
                }
            )
            ->add('header', 'root', 'header');

        $view = $this->getLayoutView();

        $this->assertBlockView(
            [ // root
                'vars'     => ['id' => 'root'],
                'children' => [
                    [ // header with changed block type
                        'vars' => ['id' => 'header', 'title' => 'test']
                    ]
                ]
            ],
            $view
        );
    }

    public function testMoveUnknownItem(): void
    {
        $this->layoutManipulator
            ->add('root', null, 'root')
            ->add('header', 'root', 'header')
            ->add('logo', 'header', 'logo')
            ->move('unknown', 'root');

        $view = $this->getLayoutView();

        $this->assertBlockView(
            [ // root
                'vars'     => ['id' => 'root'],
                'children' => [
                    [ // header
                        'vars'     => ['id' => 'header'],
                        'children' => [
                            [ // logo
                                'vars' => ['id' => 'logo', 'title' => '']
                            ]
                        ]
                    ]
                ]
            ],
            $view
        );
    }

    public function testMoveToUnknownParent(): void
    {
        $this->layoutManipulator
            ->add('root', null, 'root')
            ->add('header', 'root', 'header')
            ->add('logo', 'header', 'logo')
            ->move('logo', 'unknown');

        $view = $this->getLayoutView();

        $this->assertBlockView(
            [ // root
                'vars'     => ['id' => 'root'],
                'children' => [
                    [ // header
                        'vars'     => ['id' => 'header'],
                        'children' => [
                            [ // logo
                                'vars' => ['id' => 'logo', 'title' => '']
                            ]
                        ]
                    ]
                ]
            ],
            $view
        );
    }

    public function testMoveToParent(): void
    {
        $this->layoutManipulator
            ->add('root', null, 'root')
            ->add('header1', 'root', 'header')
            ->add('container1', 'header1', ContainerType::NAME)
            ->add('logo1', 'container1', 'logo')
            ->add('header2', 'root', 'header')
            ->add('container2', 'header2', ContainerType::NAME)
            ->add('logo2', 'container2', 'logo')
            ->move('container1', 'root');

        $view = $this->getLayoutView();

        $this->assertBlockView(
            [ // root
                'vars'     => ['id' => 'root'],
                'children' => [
                    [ // header1
                        'vars' => ['id' => 'header1']
                    ],
                    [ // header2
                        'vars'     => ['id' => 'header2'],
                        'children' => [
                            [ // container2
                                'vars'     => ['id' => 'container2'],
                                'children' => [
                                    [ // logo2
                                        'vars' => ['id' => 'logo2', 'title' => '']
                                    ]
                                ]
                            ]
                        ]
                    ],
                    [ // container1
                        'vars'     => ['id' => 'container1'],
                        'children' => [
                            [ // logo1
                                'vars' => ['id' => 'logo1', 'title' => '']
                            ]
                        ]
                    ]
                ]
            ],
            $view
        );
    }

    public function testMoveToAnotherContainer(): void
    {
        $this->layoutManipulator
            ->add('root', null, 'root')
            ->add('header1', 'root', 'header')
            ->add('container1', 'header1', ContainerType::NAME)
            ->add('logo1', 'container1', 'logo')
            ->add('header2', 'root', 'header')
            ->add('container2', 'header2', ContainerType::NAME)
            ->add('logo2', 'container2', 'logo')
            ->move('container1', 'header2');

        $view = $this->getLayoutView();

        $this->assertBlockView(
            [ // root
                'vars'     => ['id' => 'root'],
                'children' => [
                    [ // header1
                        'vars' => ['id' => 'header1']
                    ],
                    [ // header2
                        'vars'     => ['id' => 'header2'],
                        'children' => [
                            [ // container2
                                'vars'     => ['id' => 'container2'],
                                'children' => [
                                    [ // logo2
                                        'vars' => ['id' => 'logo2', 'title' => '']
                                    ]
                                ]
                            ],
                            [ // container1
                                'vars'     => ['id' => 'container1'],
                                'children' => [
                                    [ // logo1
                                        'vars' => ['id' => 'logo1', 'title' => '']
                                    ]
                                ]
                            ]
                        ]
                    ],
                ]
            ],
            $view
        );
    }

    public function testMoveToAnotherContainerBeforeSibling(): void
    {
        $this->layoutManipulator
            ->add('root', null, 'root')
            ->add('header1', 'root', 'header')
            ->add('container1', 'header1', ContainerType::NAME)
            ->add('logo1', 'container1', 'logo')
            ->add('header2', 'root', 'header')
            ->add('container2', 'header2', ContainerType::NAME)
            ->add('logo2', 'container2', 'logo')
            ->add('container3', 'header2', ContainerType::NAME)
            ->add('logo3', 'container3', 'logo')
            ->move('container1', 'header2', 'container3', true);

        $view = $this->getLayoutView();

        $this->assertBlockView(
            [ // root
                'vars'     => ['id' => 'root'],
                'children' => [
                    [ // header1
                        'vars' => ['id' => 'header1']
                    ],
                    [ // header2
                        'vars'     => ['id' => 'header2'],
                        'children' => [
                            [ // container2
                                'vars'     => ['id' => 'container2'],
                                'children' => [
                                    [ // logo2
                                        'vars' => ['id' => 'logo2', 'title' => '']
                                    ]
                                ]
                            ],
                            [ // container1
                                'vars'     => ['id' => 'container1'],
                                'children' => [
                                    [ // logo1
                                        'vars' => ['id' => 'logo1', 'title' => '']
                                    ]
                                ]
                            ],
                            [ // container3
                                'vars'     => ['id' => 'container3'],
                                'children' => [
                                    [ // logo3
                                        'vars' => ['id' => 'logo3', 'title' => '']
                                    ]
                                ]
                            ],
                        ]
                    ],
                ]
            ],
            $view
        );
    }

    public function testMoveToAnotherContainerAfterSibling(): void
    {
        $this->layoutManipulator
            ->add('root', null, 'root')
            ->add('header1', 'root', 'header')
            ->add('container1', 'header1', ContainerType::NAME)
            ->add('logo1', 'container1', 'logo')
            ->move('container1', 'header2', 'container2')
            ->add('header2', 'root', 'header')
            ->add('container2', 'header2', ContainerType::NAME)
            ->add('logo2', 'container2', 'logo')
            ->add('container3', 'header2', ContainerType::NAME)
            ->add('logo3', 'container3', 'logo');

        $view = $this->getLayoutView();

        $this->assertBlockView(
            [ // root
                'vars'     => ['id' => 'root'],
                'children' => [
                    [ // header1
                        'vars' => ['id' => 'header1']
                    ],
                    [ // header2
                        'vars'     => ['id' => 'header2'],
                        'children' => [
                            [ // container2
                                'vars'     => ['id' => 'container2'],
                                'children' => [
                                    [ // logo2
                                        'vars' => ['id' => 'logo2', 'title' => '']
                                    ]
                                ]
                            ],
                            [ // container1
                                'vars'     => ['id' => 'container1'],
                                'children' => [
                                    [ // logo1
                                        'vars' => ['id' => 'logo1', 'title' => '']
                                    ]
                                ]
                            ],
                            [ // container3
                                'vars'     => ['id' => 'container3'],
                                'children' => [
                                    [ // logo3
                                        'vars' => ['id' => 'logo3', 'title' => '']
                                    ]
                                ]
                            ],
                        ]
                    ],
                ]
            ],
            $view
        );
    }

    public function testMoveToAnotherContainerBeforeUnknownSibling(): void
    {
        $this->layoutManipulator
            ->add('root', null, 'root')
            ->add('header1', 'root', 'header')
            ->add('container1', 'header1', ContainerType::NAME)
            ->add('logo1', 'container1', 'logo')
            ->move('container1', 'header2', 'unknown', true)
            ->add('header2', 'root', 'header')
            ->add('container2', 'header2', ContainerType::NAME)
            ->add('logo2', 'container2', 'logo')
            ->add('container3', 'header2', ContainerType::NAME)
            ->add('logo3', 'container3', 'logo');

        $view = $this->getLayoutView();

        $this->assertBlockView(
            [ // root
                'vars'     => ['id' => 'root'],
                'children' => [
                    [ // header1
                        'vars' => ['id' => 'header1']
                    ],
                    [ // header2
                        'vars'     => ['id' => 'header2'],
                        'children' => [
                            [ // container1
                                'vars'     => ['id' => 'container1'],
                                'children' => [
                                    [ // logo1
                                        'vars' => ['id' => 'logo1', 'title' => '']
                                    ]
                                ]
                            ],
                            [ // container2
                                'vars'     => ['id' => 'container2'],
                                'children' => [
                                    [ // logo2
                                        'vars' => ['id' => 'logo2', 'title' => '']
                                    ]
                                ]
                            ],
                            [ // container3
                                'vars'     => ['id' => 'container3'],
                                'children' => [
                                    [ // logo3
                                        'vars' => ['id' => 'logo3', 'title' => '']
                                    ]
                                ]
                            ],
                        ]
                    ],
                ]
            ],
            $view
        );
    }

    public function testMoveToAnotherContainerAfterUnknownSibling(): void
    {
        $this->layoutManipulator
            ->add('root', null, 'root')
            ->add('header1', 'root', 'header')
            ->add('container1', 'header1', ContainerType::NAME)
            ->add('logo1', 'container1', 'logo')
            ->add('header2', 'root', 'header')
            ->add('container2', 'header2', ContainerType::NAME)
            ->add('logo2', 'container2', 'logo')
            ->add('container3', 'header2', ContainerType::NAME)
            ->add('logo3', 'container3', 'logo')
            ->move('container1', 'header2', 'unknown');

        $view = $this->getLayoutView();

        $this->assertBlockView(
            [ // root
                'vars'     => ['id' => 'root'],
                'children' => [
                    [ // header1
                        'vars' => ['id' => 'header1']
                    ],
                    [ // header2
                        'vars'     => ['id' => 'header2'],
                        'children' => [
                            [ // container2
                                'vars'     => ['id' => 'container2'],
                                'children' => [
                                    [ // logo2
                                        'vars' => ['id' => 'logo2', 'title' => '']
                                    ]
                                ]
                            ],
                            [ // container3
                                'vars'     => ['id' => 'container3'],
                                'children' => [
                                    [ // logo3
                                        'vars' => ['id' => 'logo3', 'title' => '']
                                    ]
                                ]
                            ],
                            [ // container1
                                'vars'     => ['id' => 'container1'],
                                'children' => [
                                    [ // logo1
                                        'vars' => ['id' => 'logo1', 'title' => '']
                                    ]
                                ]
                            ],
                        ]
                    ],
                ]
            ],
            $view
        );
    }

    /**
     * tests 'move' item within the same parent when the target item is added only in second iteration
     * and as result it is expected that this 'move' action is executed in the second iteration as well
     */
    public function testMoveWithinSameParentBeforeButInSecondIteration(): void
    {
        // first iteration
        $this->layoutManipulator
            ->add('root', null, 'root')
            ->add('header', 'root', 'header')
            ->add('logo2', 'header', 'logo')
            ->move('logo2', null, 'logo1', true);
        $this->layoutManipulator->applyChanges($this->context);

        // second iteration
        $this->layoutManipulator
            ->add('logo1', 'header', 'logo');
        $view = $this->getLayoutView();

        $this->assertBlockView(
            [ // root
                'vars'     => ['id' => 'root'],
                'children' => [
                    [ // header
                        'vars'     => ['id' => 'header'],
                        'children' => [
                            [ // logo2
                                'vars' => ['id' => 'logo2', 'title' => '']
                            ],
                            [ // logo1
                                'vars' => ['id' => 'logo1', 'title' => '']
                            ]
                        ]
                    ]
                ]
            ],
            $view
        );
    }

    public function testLayoutChangedByBlockType(): void
    {
        $this->layoutManipulator
            ->add('root', null, 'root')
            ->add('header', 'root', 'header')
            ->add('logo', 'header', 'logo', ['title' => 'test'])
            ->add('test_container', 'header', 'test_self_building_container');

        $view = $this->getLayoutView();

        $this->assertBlockView(
            [ // root
                'vars'     => ['id' => 'root'],
                'children' => [
                    [ // header
                        'vars'     => ['id' => 'header'],
                        'children' => [
                            [ // logo
                                'vars' => ['id' => 'logo', 'title' => 'test']
                            ],
                            [ // test_container
                                'vars'     => ['id' => 'test_container'],
                                'children' => [
                                    [ // logo added by 'test_self_building_container' block type
                                        'vars' => ['id' => 'test_container_logo', 'title' => '']
                                    ],
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            $view
        );
    }

    public function testSetBlockTheme(): void
    {
        $this->layoutManipulator
            ->setBlockTheme(['@My/Layout/theme1.html.twig', '@My/Layout/theme2.html.twig'])
            ->add('root', null, 'root')
            ->add('header', 'root', 'header')
            ->add('logo', 'header', 'logo')
            ->setBlockTheme('@My/Layout/my_theme.html.twig', 'logo')
            ->setBlockTheme('@My/Layout/theme3.html.twig');

        $view = $this->getLayoutView();

        $this->assertBlockView(
            [ // root
                'vars'     => ['id' => 'root'],
                'children' => [
                    [ // header
                        'vars'     => ['id' => 'header'],
                        'children' => [
                            [ // logo
                                'vars' => ['id' => 'logo', 'title' => '']
                            ]
                        ]
                    ]
                ]
            ],
            $view
        );

        $blockThemes = $this->rawLayoutBuilder->getRawLayout()->getBlockThemes();
        $this->assertSame(
            [
                'root' => [
                    '@My/Layout/theme1.html.twig',
                    '@My/Layout/theme2.html.twig',
                    '@My/Layout/theme3.html.twig'
                ],
                'logo' => [
                    '@My/Layout/my_theme.html.twig'
                ]
            ],
            $blockThemes
        );
    }

    public function testSetFormTheme(): void
    {
        $this->layoutManipulator
            ->add('root', null, 'root')
            ->setFormTheme(['@My/Layout/form_theme1.html.twig', '@My/Layout/form_theme2.html.twig']);

        $this->getLayoutView();

        $formThemes = $this->rawLayoutBuilder->getRawLayout()->getFormThemes();
        $this->assertSame(
            ['@My/Layout/form_theme1.html.twig', '@My/Layout/form_theme2.html.twig'],
            $formThemes
        );
    }

    public function testLayoutUpdates(): void
    {
        $this->registry->addExtension(
            new PreloadedExtension(
                [],
                [],
                [
                    'header' => [
                        new CallbackLayoutUpdate(
                            function (LayoutManipulatorInterface $layoutManipulator, LayoutItemInterface $item) {
                                $layoutManipulator->add('logo2', $item->getParentId(), 'logo');
                                $layoutManipulator->add('logo3', $item->getId(), 'logo');
                            }
                        )
                    ]
                ]
            )
        );

        $this->layoutManipulator
            ->add('root', null, 'root')
            ->add('header', 'root', 'header')
            ->add('logo1', 'header', 'logo');

        $view = $this->getLayoutView();

        $this->assertBlockView(
            [ // root
                'vars'     => ['id' => 'root'],
                'children' => [
                    [ // header
                        'vars'     => ['id' => 'header'],
                        'children' => [
                            [ // logo1
                                'vars' => ['id' => 'logo1', 'title' => '']
                            ],
                            [ // logo3
                                'vars' => ['id' => 'logo3', 'title' => '']
                            ]
                        ]
                    ],
                    [ // logo2
                        'vars' => ['id' => 'logo2', 'title' => '']
                    ]
                ]
            ],
            $view
        );
    }

    public function testLayoutUpdatesWhenParentIsAddedInUpdate(): void
    {
        $this->registry->addExtension(
            new PreloadedExtension(
                [],
                [],
                [
                    'header' => [
                        new CallbackLayoutUpdate(
                            function (LayoutManipulatorInterface $layoutManipulator, LayoutItemInterface $item) {
                                $layoutManipulator->add('logo2', $item->getParentId(), 'logo');
                                $layoutManipulator->add('logo3', $item->getId(), 'logo');
                            }
                        )
                    ],
                    'root'   => [
                        new CallbackLayoutUpdate(
                            function (LayoutManipulatorInterface $layoutManipulator, LayoutItemInterface $item) {
                                $layoutManipulator->add('header', $item->getId(), 'header');
                            }
                        )
                    ]
                ]
            )
        );

        $this->layoutManipulator
            ->add('root', null, 'root')
            ->add('logo1', 'header', 'logo');

        $view = $this->getLayoutView();

        $this->assertBlockView(
            [ // root
                'vars'     => ['id' => 'root'],
                'children' => [
                    [ // header
                        'vars'     => ['id' => 'header'],
                        'children' => [
                            [ // logo1
                                'vars' => ['id' => 'logo1', 'title' => '']
                            ],
                            [ // logo3
                                'vars' => ['id' => 'logo3', 'title' => '']
                            ]
                        ]
                    ],
                    [ // logo2
                        'vars' => ['id' => 'logo2', 'title' => '']
                    ]
                ]
            ],
            $view
        );
    }

    /**
     * test the case when removing siblingId for 'add' does not help and siblingId must be restored
     */
    public function testLayoutUpdatesWhenUpdateLinkedWithAddToUndefinedSiblingAndAddDependsToUpdate(): void
    {
        $this->registry->addExtension(
            new PreloadedExtension(
                [],
                [],
                [
                    'header' => [
                        new CallbackLayoutUpdate(
                            function (LayoutManipulatorInterface $layoutManipulator, LayoutItemInterface $item) {
                                $layoutManipulator->add('logo2', $item->getParentId(), 'logo');
                                $layoutManipulator->add('logo3', $item->getId(), 'logo');
                                $layoutManipulator->add('logo4', $item->getId(), 'logo');
                            }
                        )
                    ]
                ]
            )
        );

        $this->layoutManipulator
            ->add('root', null, 'root')
            ->add('logo1', 'header', 'logo', [], 'logo4', true)
            ->add('header', 'root', 'header', [], 'unknown');

        $view = $this->getLayoutView();

        $this->assertBlockView(
            [ // root
                'vars'     => ['id' => 'root'],
                'children' => [
                    [ // header
                        'vars'     => ['id' => 'header'],
                        'children' => [
                            [ // logo3
                                'vars' => ['id' => 'logo3', 'title' => '']
                            ],
                            [ // logo1
                                'vars' => ['id' => 'logo1', 'title' => '']
                            ],
                            [ // logo4
                                'vars' => ['id' => 'logo4', 'title' => '']
                            ]
                        ]
                    ],
                    [ // logo2
                        'vars' => ['id' => 'logo2', 'title' => '']
                    ]
                ]
            ],
            $view
        );
    }

    /**
     * test the case when removing siblingId for 'move' does not help and siblingId must be restored
     */
    public function testLayoutUpdatesWhenUpdateLinkedWithAddToUndefinedSiblingAndMoveDependsToUpdate(): void
    {
        $this->registry->addExtension(
            new PreloadedExtension(
                [],
                [],
                [
                    'header' => [
                        new CallbackLayoutUpdate(
                            function (LayoutManipulatorInterface $layoutManipulator, LayoutItemInterface $item) {
                                $layoutManipulator->add('logo2', $item->getParentId(), 'logo');
                                $layoutManipulator->add('logo3', $item->getId(), 'logo');
                                $layoutManipulator->add('logo4', $item->getId(), 'logo');
                            }
                        )
                    ]
                ]
            )
        );

        $this->layoutManipulator
            ->add('root', null, 'root')
            ->move('logo1', 'header', 'logo4', true)
            ->add('logo1', 'header', 'logo', [])
            ->add('header', 'root', 'header', [], 'unknown');

        $view = $this->getLayoutView();

        $this->assertBlockView(
            [ // root
                'vars'     => ['id' => 'root'],
                'children' => [
                    [ // header
                        'vars'     => ['id' => 'header'],
                        'children' => [
                            [ // logo3
                                'vars' => ['id' => 'logo3', 'title' => '']
                            ],
                            [ // logo1
                                'vars' => ['id' => 'logo1', 'title' => '']
                            ],
                            [ // logo4
                                'vars' => ['id' => 'logo4', 'title' => '']
                            ]
                        ]
                    ],
                    [ // logo2
                        'vars' => ['id' => 'logo2', 'title' => '']
                    ]
                ]
            ],
            $view
        );
    }

    public function testGetNotAppliedActions(): void
    {
        $this->layoutManipulator->add('add_action_id', 'parent_action_id', 'blockType');
        $this->layoutManipulator->remove('remove_action_id');
        $this->layoutManipulator->move('move_action_id', 'parent_action_id');

        $expected = [
            [
                'name' => 'add',
                'args' => [
                    'id' => 'add_action_id',
                    'parentId' => 'parent_action_id',
                    'blockType' => 'blockType'
                ]
            ],
            [
                'name' => 'move',
                'args' => [
                    'id' => 'move_action_id',
                    'parentId' => 'parent_action_id'
                ]
            ],
            [
                'name' => 'remove',
                'args' => [
                    'id' => 'remove_action_id'
                ]
            ]
        ];
        $this->assertSame($expected, $this->layoutManipulator->getNotAppliedActions());
    }
}
