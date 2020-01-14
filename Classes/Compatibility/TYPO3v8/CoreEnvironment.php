<?php
namespace Typoworx\FooBar\Compatibility\TYPO3v8;

use TYPO3\CMS\Composer\Plugin\Core\InstallerScripts\AutoloadConnector;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Core\ApplicationContext;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;

/**
 * Class CoreEnvironment
 *
 * Provides a Shim-Class in TYPO3 v8. for \TYPO3\CMS\Core\Core\Environment
 * to simplify migration to TYPO3 v9.
 */
class CoreEnvironment implements SingletonInterface, CoreEnvironmentInterface
{
    const OS_WINDOWS = 'WINDOWS';
    const OS_UNIX    = 'UNIX';

    private static $initialized = false;

    protected static $cli;
    protected static $os;
    protected static $composerMode;
    protected static $projectPath;
    protected static $publicPath;
    protected static $currentScript;

#region CoreEnvironment Shim
    public static function initialize()
    {
        if(self::isCoreEnvironmentClassAvailable())
        {
            self::$cli         = \TYPO3\CMS\Core\Core\Environment::isWindows() ? self::OS_WINDOWS : self::OS_UNIX;

            self::$projectPath = \TYPO3\CMS\Core\Core\Environment::getProjectPath();
            self::$publicPath  = \TYPO3\CMS\Core\Core\Environment::getPublicPath();
            self::$currentScript  = \TYPO3\CMS\Core\Core\Environment::getCurrentScript();
        }
        else
        {
            self::$cli          = DIRECTORY_SEPARATOR === '\\' ? self::OS_UNIX : self::OS_WINDOWS;
            self::$composerMode = defined('TYPO3_COMPOSER_MODE') && TYPO3_COMPOSER_MODE === TRUE;

            self::$publicPath   = (string)constant('PATH_site');

            if(is_file(self::$publicPath . 'composer.json'))
            {
                self::$projectPath= realpath(self::$publicPath);
            }
            else if(is_file(self::$publicPath  . '../composer.json'))
            {
                self::$projectPath= realpath(self::$publicPath . '../');
            }

            self::$currentScript = $_SERVER['PHP_SELF'];
        }
    }

    /**
     * @return bool
     */
    protected static function isCoreEnvironmentClassAvailable() : bool
    {
        static $available;

        if($available === null)
        {
            $available = class_exists(\TYPO3\CMS\Core\Core\Environment::class);
        }

        return $available;
    }

    /**
     * Deligates all calls to Core\Environment class if available
     * @param string $method
     * @param array $arguments
     * @return mixed
     */
    protected static function deligateToCoreClass(string $method, array $arguments)
    {
        return call_user_func_array(\TYPO3\CMS\Core\Core\Environment::class . '::' . $method, $arguments);
    }
#endregion

    #region TYPO3 v9 Core-Environment Legacy-Methods
    /**
     * @return string
     */
    public static function getProjectPath(): string
    {
        self::$initialized || self::initialize();

        return self::$projectPath;
    }

    /**
     * @return string
     */
    public static function getPublicPath() : string
    {
        self::$initialized || self::initialize();

        return self::$publicPath;
    }

    /**
     * @return \TYPO3\CMS\Core\Core\ApplicationContext
     */
    public static function getContext(): ApplicationContext
    {
        return GeneralUtility::getApplicationContext();
    }

    /**
     * @return bool
     */
    public static function isComposerMode(): bool
    {
        self::$initialized || self::initialize();

        return self::$composerMode;
    }

    public static function isCli(): bool
    {
        self::$initialized || self::initialize();

        return self::$cli;
    }

    /**
     * @return string
     */
    public static function getVarPath() : string
    {
        /**
         * Attention: TYPO3 v9 decides if Composer-Mode enabled,
         * then var still is in typo3temp/
         * else it is in {ProjectRoot}/
         */
        return self::getPublicPath() . '/typo3temp/var';
    }

    /**
     * @return string
     */
    public static function getConfigPath(): string
    {
        self::$initialized || self::initialize();

        return self::$configPath;
    }

    /**
     * @return string
     */
    public static function getCurrentScript(): string
    {
        self::$initialized || self::initialize();

        return self::$currentScript;
    }

    public static function getLabelsPath(): string
    {
        if (self::$publicPath === self::$projectPath) {
            return self::getPublicPath() . '/typo3conf/l10n';
        }
        return self::getVarPath() . '/labels';
    }

    /**
     * @return string
     */
    public static function getBackendPath(): string
    {
        return self::getPublicPath() . '/typo3';
    }

    /**
     * @return string
     */
    public static function getFrameworkBasePath(): string
    {
        return self::getPublicPath() . '/typo3/sysext';
    }

    /**
     * @return string
     */
    public static function getExtensionsPath(): string
    {
        return self::getPublicPath() . '/typo3conf/ext';
    }

    /**
     * @return string
     */
    public static function getLegacyConfigPath(): string
    {
        return self::getPublicPath() . '/typo3conf';
    }

    /**
     * @return bool
     */
    public static function isWindows(): bool
    {
        return self::$os === self::OS_WINDOWS;
    }

    /**
     * @return bool
     */
    public static function isUnix(): bool
    {
        return self::$os === self::OS_UNIX;
    }
#endregion
}
