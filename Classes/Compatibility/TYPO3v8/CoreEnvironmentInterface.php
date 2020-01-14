<?php
namespace Typoworx\FooBar\Compatibility\TYPO3v8;

use TYPO3\CMS\Core\Core\ApplicationContext;

/**
 * Interface CoreEnvironmentInterface
 *
 * Provides a Shim-Class in TYPO3 v8. for \TYPO3\CMS\Core\Core\Environment
 */
interface CoreEnvironmentInterface
{
    public static function getContext(): ApplicationContext;

    public static function isComposerMode(): bool;

    public static function isCli(): bool;


    public static function getProjectPath(): string;
    public static function getVarPath(): string;
    public static function getConfigPath(): string;
    public static function getCurrentScript(): string;
    public static function getLabelsPath(): string;
    public static function getBackendPath(): string;
    public static function getFrameworkBasePath(): string;
    public static function getExtensionsPath(): string;
    public static function getLegacyConfigPath(): string;
    public static function isWindows(): bool;
    public static function isUnix(): bool;
}
