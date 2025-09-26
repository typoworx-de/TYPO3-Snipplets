<?php
declare(strict_types=1);
namespace Foo\Bar\ViewHelpers\Image\Traits;

use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Core\Resource\Exception\InvalidFileException;
use TYPO3\CMS\Extbase\Domain\Model\FileReference as ExtbaseFileReference;
use TYPO3\CMS\Core\Resource\FileReference;

trait FileTraits
{
      protected function imageFactory(FileInterface|FileReference|string|int $fileResource) : FileInterface|string|null
    {
        $file = null;

        if (is_string($fileResource) && str_contains($fileResource, ':') && !str_contains($fileResource, 'EXT:'))
        {
            [$objectType ,$fileReferenceId] = GeneralUtility::trimExplode(':', $fileResource);
            $factory = GeneralUtility::makeInstance(ResourceFactory::class);

            if ($objectType === FileReference::class)
            {
                $file = $factory->getFileReferenceObject($fileReferenceId);
            }
        }
        else if (is_string($fileResource) && str_contains($fileResource, 'EXT:'))
        {
            try
            {
                $file = PathUtility::getPublicResourceWebPath($fileResource);
            }
            catch (InvalidFileException $e)
            {
                $file = sprintf('%s%s', Environment::getPublicPath(), $fileResource);

                if (!is_file($file))
                {
                    throw $e;
                }
            }
        }
        else if (is_int($fileResource))
        {
            $uid = (int)$fileResource;

            try
            {
                $factory = GeneralUtility::makeInstance(ResourceFactory::class);
                $file = $factory->getFileReferenceObject($uid)->getOriginalFile();
            }
            catch (\Throwable $e)
            {
                return null;
            }
        }
        else if ($fileResource instanceof ExtbaseFileReference)
        {
            $file = $fileResource->getOriginalResource()->getOriginalFile();
        }
        elseif ($fileResource instanceof FileInterface)
        {
            $file = $fileResource;
        }

        return $file;
    }
}
