<?php
namespace Foo\FooBar\Domain\TypeConverter;

use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Extbase\Error\Error;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Extbase\Property\TypeConverter\AbstractTypeConverter;
use TYPO3\CMS\Extbase\Property\PropertyMappingConfigurationInterface;

use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Resource\File as CoreFile;
use TYPO3\CMS\Core\Resource\FileReference as CoreFileReference;
use TYPO3\CMS\Extbase\Domain\Model\FileReference as ExtbaseFileReference;

use TYPO3\CMS\Extbase\Security\Cryptography\HashService;

/**
 * Class UploadedFileReferenceConverter
 * @package Foo\FooBar\Domain\TypeConverter
 *
 * ----------------------------------------
 * Usage:
 * ----------------------------------------
 * ext_localconf.php
 * ```
 * // Register our own File-Upload Handler for Models handling File-Uploads
 * \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerTypeConverter(
 *    \Foo\FooBar\Domain\TypeConverter\UploadedFileReferenceConverter::class
 * );
 * ```
 *
 * Controller
 * ```
 * use Foo\FooBar\Domain\TypeConverter\UploadedFileReferenceConverter;
 * 
 * public function initializeAction()
 * {
 *    parent::initializeAction();

 *    if ($this->arguments->hasArgument('myModel'))
 *    {
 *        $eintrag = $this->arguments->getArgument('myModel');
 *        $propertyMapper = $eintrag->getPropertyMappingConfiguration();
 *
 *        $myFileProperty = $propertyMapper->forProperty('logo');
 *        $myFileProperty->setTypeConverter($this->objectManager->get(UploadedFileReferenceConverter::class));
 *        $myFileProperty->setTypeConverterOptions(
 *            UploadedFileReferenceConverter::class,
 *            [
 *                'storageUid' => 1,
 *                'storageFolder' => $this->settings['imageStorageFolder']
 *            ]
 *       );
 *    }
 * }
 * ```
 */
class UploadedFileReferenceConverter extends AbstractTypeConverter
{
    /**
     * @var array<string>
     */
    protected $sourceTypes = ['array'];

    /**
     * @var string
     */
    protected $targetType = ExtbaseFileReference::class;

    /**
     * Prevent precedence over the available ObjectStorageConverter (low priority allowing manual-selection)
     *
     * @var int
     */
    protected $priority = 1;

    /**
     * @var \TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * @var \TYPO3\CMS\Core\Resource\ResourceFactory
     */
    protected $resourceFactory;

    /**
     * @var \TYPO3\CMS\Extbase\Security\Cryptography\HashService
     */
    protected $hashService;


    /**
     * @param \TYPO3\CMS\Core\Resource\ResourceFactory $resourceFactory
     * @internal
     */
    public function injectResourceFactory(ResourceFactory $resourceFactory)
    {
        $this->resourceFactory = $resourceFactory;
    }

    /**
     * @param \TYPO3\CMS\Extbase\Security\Cryptography\HashService $hashService
     * @internal
     */
    public function injectHashService(HashService $hashService)
    {
        $this->hashService = $hashService;
    }


    /**
     * @param mixed $source
     * @param string $targetType
     * @param array $convertedChildProperties
     * @param \TYPO3\CMS\Extbase\Property\PropertyMappingConfigurationInterface|null $configuration
     * @return mixed|\TYPO3\CMS\Extbase\Error\Error|\TYPO3\CMS\Extbase\Persistence\ObjectStorage|null
     * @throws \Throwable
     */
    public function convertFrom($source, $targetType, array $convertedChildProperties = [], PropertyMappingConfigurationInterface $configuration = null)
    {
        $converted = null;

        if(!count($source))
        {
            return;
        }

        $targetSubType = null;
        if (strpos($targetType, '<') !== false && preg_match('~([^\<]+)<?([^\>]+)>?~', $targetType, $match))
        {
            $targetSubType = array_pop($match);
            $targetType = array_pop($match);

            if ($targetSubType !== $this->getSupportedTargetType())
            {
                return;
            }
        }

        try
        {
            if ($this->checkUploadPayload($source))
            {
                try
                {
                    $resource = $this->processFalUpload($source, $configuration);

                    if ($targetType === ObjectStorage::class)
                    {
                        $objectStorage = new ObjectStorage();

                        if ($resource !== null)
                        {
                            $objectStorage->attach($resource);
                        }

                        $converted = $objectStorage;
                    }
                    else
                    {
                        $converted = $resource;
                    }
                }
                catch (\Throwable $e)
                {
                    return new Error($e->getMessage(), $e->getCode());
                }
            }
            else
            {
                $objectStorage = new ObjectStorage();

                foreach($source as $fileItem)
                {
                    if (!$this->checkUploadPayload($fileItem))
                    {
                        continue;
                    }

                    try
                    {
                        $resource = $this->processFalUpload($fileItem, $configuration);

                        if($resource !== null)
                        {
                            $objectStorage->attach($resource);
                        }
                    }
                    catch (\Throwable $e)
                    {
                        return new Error($e->getMessage(), $e->getCode());
                    }
                }

                $converted = $objectStorage;
            }
        }
        catch (\Throwable $e)
        {
            if (!GeneralUtility::getApplicationContext()->isProduction())
            {
                throw $e;
            }

            return null;
        }

        return $converted;
    }

    /**
     * @param array $source
     * @return bool
     */
    protected function checkUploadPayload(array $source): bool
    {
        if (!empty($source['error']) && $source['error'] > 0)
        {
            return false;

            /*
            // @ToDo handle possible Error-Types
            if ($source['error'] === \UPLOAD_ERR_NO_FILE)
            {
            }
            else if ($source['error'] === \UPLOAD_ERR_INI_SIZE)
            {
            }
            else if ($source['error'] === \UPLOAD_ERR_FORM_SIZE)
            {
            }
            else if ($source['error'] === \UPLOAD_ERR_PARTIAL)
            {
            }
            */
        }

        if (empty($source['size']) || $source['size'] == 0)
        {
            return false;
        }

        if (empty($source['name']) || empty($source['tmp_name']) || !is_file($source['tmp_name']))
        {
            return false;
        }

        return true;
    }

    /**
     * @param array $fileUploadArray
     * @param \TYPO3\CMS\Extbase\Property\PropertyMappingConfigurationInterface|null $configuration
     * @return \TYPO3\CMS\Extbase\Domain\Model\FileReference
     * @throws \TYPO3\CMS\Core\Resource\Exception\ExistingTargetFolderException
     * @throws \TYPO3\CMS\Core\Resource\Exception\FileDoesNotExistException
     * @throws \TYPO3\CMS\Core\Resource\Exception\InsufficientFolderAccessPermissionsException
     * @throws \TYPO3\CMS\Core\Resource\Exception\InsufficientFolderReadPermissionsException
     * @throws \TYPO3\CMS\Core\Resource\Exception\InsufficientFolderWritePermissionsException
     * @throws \TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException
     * @throws \TYPO3\CMS\Extbase\Security\Exception\InvalidArgumentForHashGenerationException
     * @throws \TYPO3\CMS\Extbase\Security\Exception\InvalidHashException
     */
    protected function processFalUpload(array $fileUploadArray, PropertyMappingConfigurationInterface $configuration = null)
    {
        $resourcePointer = null;
        $fileReferenceModel = null;

        if (isset($source['submittedFile']['resourcePointer']))
        {
            try
            {
                // File references use numeric resource pointers, direct
                // file relations are using "file:" prefix (e.g. "file:5")
                $resourcePointer = $this->hashService->validateAndStripHmac($fileUploadArray['submittedFile']['resourcePointer']);

                if (strpos($resourcePointer, 'file:') === 0)
                {
                    $fileUid = (int)substr($resourcePointer, 5);
                    return $this->createFileReferenceFromFalFileObject($this->resourceFactory->getFileObject($fileUid));
                }

                $fileReferenceModel = $this->createFileReferenceFromFalFileReferenceObject(
                    $this->resourceFactory->getFileReferenceObject($resourcePointer),
                    (int)$resourcePointer
                );
            }
            catch (\InvalidArgumentException $e)
            {
                // Nothing to do. No file is uploaded and resource pointer is invalid. Discard!
            }
        }
        else
        {
            $storageUid = $configuration->getConfigurationValue(self::class, 'storageUid')
                ?: 1 // Fileadmin default Storage
            ;

            $storageFolderPath = $configuration->getConfigurationValue(self::class, 'storageFolder');

            $conflictMode = $configuration->getConfigurationValue(self::class, 'conflictMode')
                ?: \TYPO3\CMS\Core\Resource\DuplicationBehavior::RENAME
            ;

            /** @var \TYPO3\CMS\Core\Resource\StorageRepository $storageRepository */
            $storageRepository = $this->objectManager->get(StorageRepository::class);
            $storage = $storageRepository->findByUid($storageUid);

            $uploadFolder = $storage->hasFolder($storageFolderPath)
                ? $storage->getFolder($storageFolderPath)
                : $storage->createFolder($storageFolderPath)
            ;

            $uploadedFile = $uploadFolder->addUploadedFile($fileUploadArray, $conflictMode);

            $fileReferenceModel = $this->createFileReferenceFromFalFileObject($uploadedFile, $resourcePointer);
        }

        return $fileReferenceModel;
    }

    /**
     * @param CoreFile $file
     * @param int $resourcePointer
     * @return ExtbaseFileReference
     * @see \TYPO3\CMS\Form\Mvc\Property\TypeConverter
     */
    protected function createFileReferenceFromFalFileObject(CoreFile $file, int $resourcePointer = null): ExtbaseFileReference
    {
        $fileReference = $this->resourceFactory->createFileReferenceObject([
            'uid_local' => $file->getUid(),
            'uid_foreign' => uniqid('NEW_'),
            'uid' => uniqid('NEW_'),
            'crop' => null,
        ]);

        return $this->createFileReferenceFromFalFileReferenceObject($fileReference, $resourcePointer);
    }

    /**
     * In case no $resourcePointer is given a new file reference domain object
     * will be returned. Otherwise the file reference is reconstituted from
     * storage and will be updated(!) with the provided $falFileReference.
     *
     * @param CoreFileReference $falFileReference
     * @param int $resourcePointer
     * @return ExtbaseFileReference
     */
    protected function createFileReferenceFromFalFileReferenceObject(CoreFileReference $falFileReference, int $resourcePointer = null): ExtbaseFileReference
    {
        if ($resourcePointer === null)
        {
            $fileReference = $this->objectManager->get(ExtbaseFileReference::class);
        }
        else
        {
            $fileReference = $this->persistenceManager->getObjectByIdentifier(
                $resourcePointer, ExtbaseFileReference::class,
                false
            );
        }

        $fileReference->setOriginalResource($falFileReference);

        return $fileReference;
    }
}
