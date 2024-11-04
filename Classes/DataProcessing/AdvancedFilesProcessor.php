<?php
declare(strict_types=1);
namespace Foo\Bar\\DataProcessing;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\RootlineUtility;
use TYPO3\CMS\Frontend\Resource\FileCollector;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\DataProcessorInterface;

/**
 * Basing on TYPO3\CMS\Frontend\DataProcessing\FilesProcessor
 *
 * implements:
 * - slide feature
 *
 * Services.yaml:
 *  Foo\Bar\DataProcessing\AdvancedFilesProcessor:
 *    tags:
 *      - name: 'data.processor'
 *        identifier: 'custom-slides
 */
class AdvancedFilesProcessor implements DataProcessorInterface
{
    public function process(ContentObjectRenderer $cObj, array $contentObjectConfiguration, array $processorConfiguration, array $processedData) : array
    {
        if (isset($processorConfiguration['if.']) && !$cObj->checkIf($processorConfiguration['if.']))
        {
            return $processedData;
        }

        // gather data
        $fileCollector = GeneralUtility::makeInstance(FileCollector::class);

        // references / relations
        if ((isset($processorConfiguration['references']) && $processorConfiguration['references']) || (isset($processorConfiguration['references.']) && $processorConfiguration['references.']))
        {
            $referencesUidList = (string)$cObj->stdWrapValue('references', $processorConfiguration);
            $referencesUids = GeneralUtility::intExplode(',', $referencesUidList, true);
            $fileCollector->addFileReferences($referencesUids);

            if (!empty($processorConfiguration['references.']))
            {
                $referenceConfiguration = $processorConfiguration['references.'] ?? [];
                $slide = $cObj->stdWrapValue('slide', $referenceConfiguration);
                $relationField = $cObj->stdWrapValue('fieldName', $referenceConfiguration);

                // If no reference fieldName is set, there's nothing to do
                if (!empty($relationField))
                {
                    // Fetch the references of the default element
                    $relationTable = $cObj->stdWrapValue('table', $referenceConfiguration, $cObj->getCurrentTable());
                    if (!empty($relationTable))
                    {
                        if ($slide === false)
                        {
                            $fileCollector->addFilesFromRelation($relationTable, $relationField, $cObj->data);
                        }
                        else
                        {
                            $rootlineUtility = GeneralUtility::makeInstance(RootlineUtility::class, $cObj->data['pid']);
                            foreach ($rootlineUtility->get() as $rootlinePage)
                            {
                                if (!empty($rootlinePage[ $relationField ] ?? null))
                                {
                                    $fileCollector->addFilesFromRelation($relationTable, $relationField, $rootlinePage);

                                    break;
                                }
                            }
                        }
                    }
                }
            }
        }

        // files
        $files = $cObj->stdWrapValue('files', $processorConfiguration);
        if ($files)
        {
            $files = GeneralUtility::intExplode(',', (string)$files, true);
            $fileCollector->addFiles($files);
        }

        // collections
        $collections = $cObj->stdWrapValue('collections', $processorConfiguration);
        if (!empty($collections))
        {
            $collections = GeneralUtility::intExplode(',', (string)$collections, true);
            $fileCollector->addFilesFromFileCollections($collections);
        }

        // folders
        $folders = $cObj->stdWrapValue('folders', $processorConfiguration);
        if (!empty($folders))
        {
            $folders = GeneralUtility::trimExplode(',', (string)$folders, true);
            $fileCollector->addFilesFromFolders($folders, !empty($processorConfiguration['folders.']['recursive']));
        }

        // make sure to sort the files
        $sortingProperty = $cObj->stdWrapValue('sorting', $processorConfiguration);
        if ($sortingProperty)
        {
            $sortingDirection = $cObj->stdWrapValue('direction', $processorConfiguration['sorting.'] ?? [], 'ascending');

            $fileCollector->sort($sortingProperty, $sortingDirection);
        }

        // set the files into a variable, default "files"
        $targetVariableName = $cObj->stdWrapValue('as', $processorConfiguration, 'files');
        $processedData[$targetVariableName] = $fileCollector->getFiles();

        return $processedData;
    }
}
