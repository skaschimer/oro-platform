<?php

namespace Oro\Bundle\TranslationBundle\Tests\Functional\Stub\Download;

use Oro\Bundle\TranslationBundle\Download\TranslationServiceAdapterInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * The stub of TranslationServiceAdapterInterface that work with local files.
 */
class TranslationServiceAdapterStub implements TranslationServiceAdapterInterface
{
    private string $archivePath;

    public function __construct(string $archivePath)
    {
        $this->archivePath = $archivePath;
    }

    #[\Override]
    public function fetchTranslationMetrics(): array
    {
        throw new \RuntimeException('Not implemented');
    }

    #[\Override]
    public function downloadLanguageTranslationsArchive(string $languageCode, string $pathToSaveDownloadedArchive): void
    {
        $filesystem = new Filesystem();
        $filesystem->copy($this->archivePath, $pathToSaveDownloadedArchive);
    }

    #[\Override]
    public function extractTranslationsFromArchive(
        string $pathToArchive,
        string $directoryPathToExtractTo,
        string $languageCode
    ): void {
        /**
         * Double check if the target directory already exist.
         * This should be done because real adapter do not check if directory exist
         * at the extractTranslationsFromArchive method
         * of {@see \Oro\Bundle\TranslationBundle\Download\OroTranslationServiceAdapter} class.
         */
        if (!\is_dir($directoryPathToExtractTo)) {
            throw new \RuntimeException(sprintf('Directory "%s" do not exists.', $directoryPathToExtractTo));
        }

        $zip = new \ZipArchive();
        $zip->open($pathToArchive);
        $zip->extractTo($directoryPathToExtractTo);
        $zip->close();
    }
}
