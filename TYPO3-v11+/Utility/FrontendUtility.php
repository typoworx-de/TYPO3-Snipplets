<?php
declare(strict_types=1);
namespace Foo\Bar\Utility;

use Psr\Container\ContainerInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContext;
use TYPO3\CMS\Frontend\ContentObject\ContentDataProcessor;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;

/**
 * This is our own Container to provide some DI services
 * as multipurpose Frontend Utility-Class f.e. for (static) ViewHelpers
 *
 * @docs:
 * - TYPO3 Core DataProcessors (and their shorthand alias):
 *   https://github.com/TYPO3/typo3/blob/main/typo3/sysext/frontend/Configuration/Services.yaml
 */
class FrontendUtility
{
    private static ?self $_instance = null;

    private ?ContainerInterface $container;
    private ?ContentDataProcessor $contentDataProcessor;
    private ?ConfigurationManagerInterface $configurationManager;

    public function __construct(
        ?ContainerInterface $container = null,
        ?ContentDataProcessor $contentDataProcessor = null,
        ?ConfigurationManagerInterface $configurationManager = null,
    ) {
        $this->container = $container;
        $this->contentDataProcessor = $contentDataProcessor;
        $this->configurationManager = $configurationManager;
    }

    public static function instance() :? self
    {
        if (static::$_instance === null)
        {
            static::$_instance = GeneralUtility::makeInstance(static::class);
        }

        return static::$_instance;
    }

    public function getContainer() : ContainerInterface
    {
        return $this->container;
    }

    public function getContentDataProcessor() : ContentDataProcessor
    {
        return $this->contentDataProcessor;
    }

    public function getConfigurationManager() : ConfigurationManagerInterface
    {
        return $this->configurationManager;
    }

    public function getContentObject(RenderingContextInterface|RenderingContext $renderingContext) : ContentObjectRenderer
    {
        return $renderingContext?->getRequest()?->getAttribute('currentContentObject');
    }

    protected static function getServerRequest() :? ServerRequest
    {
        return $GLOBALS['TYPO3_REQUEST'];
    }

    public static function getSite() : null|SiteInterface|Site
    {
        return static::getServerRequest()?->getAttribute('site');
    }

    public static function getSiteLanguage() :? SiteLanguage
    {
        return static::getServerRequest()?->getAttribute('language');
    }

    public static function translate(string $key, ?string $extensionName = null, ?array $arguments = null, ?string $languageKey = null, ?array $alternativeLanguageKeys = null, mixed $default = null, ?string $cast = null) : mixed
    {
        if (empty($key))
        {
            return null;
        }

        $return = LocalizationUtility::translate(
            key: $key,
            extensionName: $extensionName,
            arguments: $arguments,
            languageKey: $languageKey,
            alternativeLanguageKeys: $alternativeLanguageKeys
        );

        $return = $return ?? $default;

        if ($cast !== null)
        {
            if ($cast === 'bool')
            {
                $return = $return === true || stripos($return, 'true') === 0;
            }

            settype($return, $cast);
        }

        if (is_string($return))
        {
            $return = trim($return);
        }

        return $return;
    }
}
