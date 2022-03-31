<?php
namespace Foo\Bar\Compatibility\TYPO3v11\Backport;

use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

/**
 * Backport for TYPO3-Versions < 11.0
 * @usage ext_localconf.php
 * IconPackageRegistration::register($extKey)
 */
class IconPackageRegistration
{
    private static $isCompatible;


    public static function isCompatible() : bool
    {
        if(static::$isCompatible === null)
        {
            $typo3Version = VersionNumberUtility::convertVersionNumberToInteger(
                VersionNumberUtility::getCurrentTypo3Version()
            );

            static::$isCompatible = $typo3Version < 11000000;
        }

        return static::$isCompatible;
    }

    public static function register(string $extKey) : void
    {
        if(!static::isCompatible())
        {
            return;
        }

        $providerFile = ExtensionManagementUtility::extPath($extKey) . 'Configuration/Icons.php';

        if(!is_file($providerFile))
        {
            return;
        }

        $icons = @require($providerFile);
        $iconRegistry = GeneralUtility::makeInstance(IconRegistry::class);

        foreach($icons as $identifier => $config)
        {
            $iconRegistry->registerIcon(
                $identifier, $config['provider'],
                [ 'source' => $config['source'] ]
            );
        }
    }
}
