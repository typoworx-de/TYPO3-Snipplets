<?php
namespace Typoworx\FooBar\Factory;

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class LockFactory
 */
class LockFactory
{
    /**
     * Returns TYPO3 Core Locker Interface
     *
     * @param string $lockIdentifier
     * @return \TYPO3\CMS\Core\Locking\FileLockStrategy|\TYPO3\CMS\Core\Locking\Locker
     * @throws \TYPO3\CMS\Core\Locking\Exception\LockCreateException
     */
    public static function create(string $lockIdentifier)
    {
        if (class_exists(\TYPO3\CMS\Core\Locking\LockFactory::class))
        {
            $locker = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Locking\LockFactory::class)->createLocker($lockIdentifier);
        }
        elseif (class_exists(\TYPO3\CMS\Core\Locking\Locker::class))
        {
            $locker = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Locking\Locker::class, $lockIdentifier);
        }
        else
        {
            $locker = null;
        }

        return $locker;
    }
}
