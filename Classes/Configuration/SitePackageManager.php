<?php
namespace Foo\FooBarExtension\Configuration;

use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Core\Configuration\SiteConfiguration;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

/**
 * Class RegisterSiteYamlConfiguration
 * @package Foo\FooBarExtension\Configuration
 *
 * Usage in ext_tables.php:
    \Foo\FooBarExtension\Configuration\SitePackageManager::loadYamlSiteConfig(
        'EXT:' . $extKey . '/Configuration/SiteConfig'
    );
 */
class SitePackageManager implements SingletonInterface
{
    /**
     * @var self
     */
    private static $_instance;

    /**
     * @return self
     */
    protected static function getInstance() : self
    {
        if(self::$_instance === null)
        {
            $class = get_called_class();
            self::$_instance = new $class();
        }

        return self::$_instance;
    }

    /**
     * @param string $configPath
     */
    public static function loadYamlSiteConfig(string $configPath)
    {
        if(strpos($configPath, 'EXT:') === 0)
        {
            $extKey = substr($configPath, strpos($configPath, ':')+1, strpos($configPath, '/')-4);

            $extPath = ExtensionManagementUtility::extPath($extKey);
            $configPath = trim(str_replace('EXT:' . $extKey, $extPath, $configPath));
        }

        if(!empty($configPath))
        {
            GeneralUtility::makeInstance(SiteConfiguration::class, $configPath);
        }
    }
}

