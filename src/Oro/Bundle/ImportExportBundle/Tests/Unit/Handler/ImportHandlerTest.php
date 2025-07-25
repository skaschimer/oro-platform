<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Handler;

use Oro\Bundle\BatchBundle\Job\Job;
use Oro\Bundle\BatchBundle\Step\ItemStep;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\ImportExportBundle\File\BatchFileManager;
use Oro\Bundle\ImportExportBundle\File\FileManager;
use Oro\Bundle\ImportExportBundle\Handler\ImportHandler;
use Oro\Bundle\ImportExportBundle\Job\JobExecutor;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Bundle\ImportExportBundle\Reader\CsvFileReader;
use Oro\Bundle\ImportExportBundle\Reader\ReaderChain;
use Oro\Bundle\ImportExportBundle\Writer\CsvEchoWriter;
use Oro\Bundle\ImportExportBundle\Writer\WriterChain;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class ImportHandlerTest extends TestCase
{
    private JobExecutor&MockObject $jobExecutor;
    private ProcessorRegistry&MockObject $processorRegistry;
    private ConfigProvider&MockObject $configProvider;
    private TranslatorInterface&MockObject $translator;
    private WriterChain&MockObject $writerChain;
    private ReaderChain&MockObject $readerChain;
    private BatchFileManager&MockObject $batchFileManager;
    private FileManager&MockObject $fileManager;
    private ImportHandler $importHandler;

    #[\Override]
    protected function setUp(): void
    {
        $this->jobExecutor = $this->createMock(JobExecutor::class);
        $this->processorRegistry = $this->createMock(ProcessorRegistry::class);
        $this->configProvider = $this->createMock(ConfigProvider::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->writerChain = $this->createMock(WriterChain::class);
        $this->readerChain = $this->createMock(ReaderChain::class);
        $this->batchFileManager = $this->createMock(BatchFileManager::class);
        $this->fileManager = $this->createMock(FileManager::class);

        $this->importHandler = new ImportHandler(
            $this->jobExecutor,
            $this->processorRegistry,
            $this->configProvider,
            $this->translator,
            $this->writerChain,
            $this->readerChain,
            $this->batchFileManager,
            $this->fileManager
        );
    }

    /**
     * @dataProvider splitImportFileDataProvider
     */
    public function testSplitImportFile(?int $batchSize, array $expectedOptions): void
    {
        $jobName = 'entity_import_from_csv';
        $processorType = 'import';

        $writer = new CsvEchoWriter();
        $reader = new CsvFileReader($this->createMock(ContextRegistry::class));

        $step = new ItemStep($processorType);
        $step->setReader($reader);
        $step->setBatchSize($batchSize);

        $job = new Job($jobName);
        $job->addStep($step);

        $this->jobExecutor->expects($this->once())
            ->method('getJob')
            ->with($processorType, $jobName)
            ->willReturn($job);

        $this->batchFileManager->expects($this->once())
            ->method('setReader')
            ->with($reader);
        $this->batchFileManager->expects($this->once())
            ->method('setWriter')
            ->with($writer);
        $this->batchFileManager->expects($this->once())
            ->method('setConfigurationOptions')
            ->with($expectedOptions);
        $this->batchFileManager->expects($this->once())
            ->method('splitFile')
            ->with('test_file.csv')
            ->willReturn(['data']);

        $this->importHandler->setImportingFileName('test_file.csv');

        $this->assertEquals(['data'], $this->importHandler->splitImportFile($jobName, $processorType, $writer));
    }

    public function splitImportFileDataProvider(): array
    {
        return [
            'with batch size' => [
                'batchSize' => 300,
                'expectedOptions' => ['batch_size' => 300],
            ],
            'without batch size' => [
                'batchSize' => null,
                'expectedOptions' => [],
            ],
        ];
    }
}
