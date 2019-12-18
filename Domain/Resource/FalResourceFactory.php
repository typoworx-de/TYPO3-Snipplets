<?php
namespace Typoworx\FooBar\Domain\Resource;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper;

/**
 * Class FalResourceFactory
 */
class FalResourceFactory extends \TYPO3\CMS\Core\Resource\ResourceFactory
{
    /**
     * @return static
     */
    public static function getInstance() : self
    {
        return GeneralUtility::makeInstance(__CLASS__);
    }

    /**
     * This will return a FileReference-Model instead of a FileReferenceObject
     * which is compatible to f.e. setImage in common Models
     *
     * @param int $contactUid
     * @return \TYPO3\CMS\Extbase\Domain\Model\FileReference|null
     * @throws \TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException
     */
    public function getFileReferenceAsModel(int $contactUid) :? \TYPO3\CMS\Extbase\Domain\Model\FileReference
    {
        $fileReference = $this->getFileReferenceObject($contactUid);
        $dataMapper = GeneralUtility::makeInstance(DataMapper::class);

        $return = null;
        if($fileReference instanceof \TYPO3\CMS\Core\Resource\FileReference)
        {
            $return = $dataMapper->map(\TYPO3\CMS\Extbase\Domain\Model\FileReference::class, [ $fileReference->getProperties() ]);
        }

        return count($return)
            ? current($return)
            : null
        ;
    }
}
