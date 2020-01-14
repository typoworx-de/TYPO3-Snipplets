<?php
namespace Typoworx\FooBar\Utility;

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class UriBuilder
 */
class UriBuilder
{
    /**
     * @return \TYPO3\CMS\Backend\Routing\UriBuilder
     */
    public static function create()
    {
        /** @var UriBuilder $uriBuilder */
        return StaticObjectManager::get(\TYPO3\CMS\Backend\Routing\UriBuilder::class);
    }

    /**
     * @return bool
     */
    public static function isSslAvailable() : bool
    {
        $sslHeader = GeneralUtility::getIndpEnv('HTTPS');

        return !empty($sslHeader) && strtolower($sslHeader) !== 'off';
    }
}
