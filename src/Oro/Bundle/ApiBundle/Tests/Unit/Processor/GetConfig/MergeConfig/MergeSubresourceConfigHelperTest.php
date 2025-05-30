<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetConfig\MergeConfig;

use Oro\Bundle\ApiBundle\Processor\GetConfig\MergeConfig\MergeActionConfigHelper;
use Oro\Bundle\ApiBundle\Processor\GetConfig\MergeConfig\MergeFilterConfigHelper;
use Oro\Bundle\ApiBundle\Processor\GetConfig\MergeConfig\MergeSorterConfigHelper;
use Oro\Bundle\ApiBundle\Processor\GetConfig\MergeConfig\MergeSubresourceConfigHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class MergeSubresourceConfigHelperTest extends TestCase
{
    private MergeActionConfigHelper&MockObject $mergeActionConfigHelper;
    private MergeFilterConfigHelper&MockObject $mergeFilterConfigHelper;
    private MergeSorterConfigHelper&MockObject $mergeSorterConfigHelper;
    private MergeSubresourceConfigHelper $mergeSubresourceConfigHelper;

    #[\Override]
    protected function setUp(): void
    {
        $this->mergeActionConfigHelper = $this->createMock(MergeActionConfigHelper::class);
        $this->mergeFilterConfigHelper = $this->createMock(MergeFilterConfigHelper::class);
        $this->mergeSorterConfigHelper = $this->createMock(MergeSorterConfigHelper::class);

        $this->mergeSubresourceConfigHelper = new MergeSubresourceConfigHelper(
            $this->mergeActionConfigHelper,
            $this->mergeFilterConfigHelper,
            $this->mergeSorterConfigHelper
        );
    }

    public function testMergeEmptySubresourceConfig(): void
    {
        $config = [];
        $subresourceConfig = [];

        $this->mergeActionConfigHelper->expects(self::never())
            ->method('mergeActionConfig');
        $this->mergeFilterConfigHelper->expects(self::never())
            ->method('mergeFiltersConfig');
        $this->mergeSorterConfigHelper->expects(self::never())
            ->method('mergeSortersConfig');

        self::assertEquals(
            [],
            $this->mergeSubresourceConfigHelper->mergeSubresourcesConfig(
                $config,
                $subresourceConfig,
                'action1',
                true,
                true,
                true
            )
        );
    }

    public function testMergeSubresourceActionConfig(): void
    {
        $config = [
            'key' => 'val'
        ];
        $subresourceConfig = [
            'actions' => [
                'action1' => [
                    'description' => 'action 1'
                ]
            ],
            'filters' => [
                'filter1' => [
                    'description' => 'filter 1'
                ]
            ],
            'sorters' => [
                'sorter1' => [
                    'property_path' => 'sorter1Field'
                ]
            ]
        ];

        $this->mergeActionConfigHelper->expects(self::once())
            ->method('mergeActionConfig')
            ->with($config, $subresourceConfig['actions']['action1'], true)
            ->willReturn(
                [
                    'key'         => 'val',
                    'description' => 'merged action 1'
                ]
            );
        $this->mergeFilterConfigHelper->expects(self::never())
            ->method('mergeFiltersConfig');
        $this->mergeSorterConfigHelper->expects(self::never())
            ->method('mergeSortersConfig');

        self::assertEquals(
            [
                'key'         => 'val',
                'description' => 'merged action 1'
            ],
            $this->mergeSubresourceConfigHelper->mergeSubresourcesConfig(
                $config,
                $subresourceConfig,
                'action1',
                true,
                false,
                false
            )
        );
    }

    public function testMergeSubresourceFiltersConfig(): void
    {
        $config = [
            'key' => 'val'
        ];
        $subresourceConfig = [
            'actions' => [
                'action1' => [
                    'description' => 'action 1'
                ]
            ],
            'filters' => [
                'filter1' => [
                    'description' => 'filter 1'
                ]
            ],
            'sorters' => [
                'sorter1' => [
                    'property_path' => 'sorter1Field'
                ]
            ]
        ];

        $this->mergeActionConfigHelper->expects(self::never())
            ->method('mergeActionConfig');
        $this->mergeFilterConfigHelper->expects(self::once())
            ->method('mergeFiltersConfig')
            ->with($config, $subresourceConfig['filters'])
            ->willReturn(
                [
                    'key'     => 'val',
                    'filters' => [
                        'filter1' => [
                            'description' => 'merged filter 1'
                        ]
                    ]
                ]
            );
        $this->mergeSorterConfigHelper->expects(self::never())
            ->method('mergeSortersConfig');

        self::assertEquals(
            [
                'key'     => 'val',
                'filters' => [
                    'filter1' => [
                        'description' => 'merged filter 1'
                    ]
                ]
            ],
            $this->mergeSubresourceConfigHelper->mergeSubresourcesConfig(
                $config,
                $subresourceConfig,
                'anotherAction',
                false,
                true,
                false
            )
        );
    }

    public function testMergeSubresourceSortersConfig(): void
    {
        $config = [
            'key' => 'val'
        ];
        $subresourceConfig = [
            'actions' => [
                'action1' => [
                    'description' => 'action 1'
                ]
            ],
            'filters' => [
                'filter1' => [
                    'description' => 'filter 1'
                ]
            ],
            'sorters' => [
                'sorter1' => [
                    'property_path' => 'sorter1Field'
                ]
            ]
        ];

        $this->mergeActionConfigHelper->expects(self::never())
            ->method('mergeActionConfig');
        $this->mergeFilterConfigHelper->expects(self::never())
            ->method('mergeFiltersConfig');
        $this->mergeSorterConfigHelper->expects(self::once())
            ->method('mergeSortersConfig')
            ->with($config, $subresourceConfig['sorters'])
            ->willReturn(
                [
                    'key'     => 'val',
                    'sorters' => [
                        'sorter1' => [
                            'property_path' => 'mergedSorter1Field'
                        ]
                    ]
                ]
            );

        self::assertEquals(
            [
                'key'     => 'val',
                'sorters' => [
                    'sorter1' => [
                        'property_path' => 'mergedSorter1Field'
                    ]
                ]
            ],
            $this->mergeSubresourceConfigHelper->mergeSubresourcesConfig(
                $config,
                $subresourceConfig,
                'anotherAction',
                false,
                false,
                true
            )
        );
    }
}
