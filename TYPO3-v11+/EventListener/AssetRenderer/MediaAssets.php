<?php
declare(strict_types=1);
namespace Mosaiq\MqSiteBase\EventListener\AssetRenderer;

use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Cache\Event\CacheFlushEvent;
use TYPO3\CMS\Core\Cache\Event\CacheWarmupEvent;
use TYPO3\CMS\Core\Page\Event\AbstractBeforeAssetRenderingEvent;

/**
 * @usage in Configuration/Services.yaml
 * Foo\Bar\EventListener\AssetRenderer\MediaAssets:
 *   class: Foo\Bar\EventListener\AssetRenderer\MediaAssets
 *   arguments:
 *     $package: Foo\BarSite
 *     $allowDefaultGFX: true
 *     $allowedExtensions: ['css', 'map']
 *     $ignoredPattern:
 *       - ~\~~
 *       - ~/Build/~
 *       - ~/Backend/~
 *   tags:
 *     - name: event.listener
 *       identifier: foo/barsite/cache-warmup-event/AssetsGarbageCollector
 *       event: TYPO3\CMS\Core\Cache\Event\CacheWarmupEvent
 *     - name: event.listener
 *       identifier: foo/barsite/cache-flush-event/InjectAssets
 *       event: TYPO3\CMS\Core\Cache\Event\CacheFlushEvent
 *     - name: event.listener
 *       identifier: foo/barsite/asset-post-processing/InjectAssets
 *       event: TYPO3\CMS\Core\Page\Event\BeforeStylesheetsRenderingEvent
 **/
final class MediaAssets
{
    private int $cacheTTL = 300;
    private bool $skipProcessing = false;
    private array $packageNamespace;
    private string $webAssetExtensionPath;
    private string $extensionResourcePublicPath;

    private ?array $ignoredPattern = [];
    private array $allowedFileTypes = [];

    private ?Registry $registry;
    private string $registryNamespace;

    protected null|AbstractBeforeAssetRenderingEvent|CacheFlushEvent|CacheWarmupEvent $event = null;


    public function __invoke(AbstractBeforeAssetRenderingEvent|CacheFlushEvent|CacheWarmupEvent $event): void
    {
        $this->event = $event;

        if ($event instanceof CacheWarmupEvent)
        {
            // @ToDo Garbage Collector!
            $this->warmup();
        }
        else if ($event instanceof CacheFlushEvent)
        {
            $this->registry->set($this->registryNamespace, 'is_cached', false);
        }
        else if ($event instanceof AbstractBeforeAssetRenderingEvent)
        {
            $this->warmup();
        }
        else
        {
            throw new \InvalidArgumentException(sprintf('Received unknown Event: %s', get_class($event)));
        }
    }

    public function __construct(
        ?string $package = null,
        array $allowedExtensions = [],
        bool $allowDefaultGFX = true,
        array $ignoredPattern = null,
        int $cacheTTL = 300
    )
    {
        $this->registry = GeneralUtility::makeInstance(Registry::class);
        $this->registryNamespace = sprintf('%s::MediaAssets', $package);
        $this->cacheTTL = $cacheTTL;

        if ($this->skip() === false)
        {
            $this->ignoredPattern = $ignoredPattern;

            $this->prepare(
                package: $package,
                allowedExtensions: $allowedExtensions,
                allowDefaultGFX: $allowDefaultGFX,
            );
        }
    }

    protected function skip() : bool
    {
        if ($this->skipProcessing === true)
        {
            return true;
        }

        $now = (new \DateTime());
        $this->skipProcessing = (bool)$this->registry->get($this->registryNamespace, 'is_cached', false);

        if ($this->skipProcessing === true)
        {
            $isGenerated = $this->registry->get($this->registryNamespace, 'is_generated', null);

            // Check if cache has invalidated
            if ($isGenerated instanceof \DateTime)
            {
                $isGenerated->modify(sprintf('+%d seconds', $this->cacheTTL ?? 300));

                if ($isGenerated < $now)
                {
                    $this->skipProcessing = false;
                    $this->registry->set($this->registryNamespace, 'is_cached', false);

                    return false;
                }
            }
        }

        $this->skipProcessing = true;
        $this->registry->get($this->registryNamespace, 'is_generated', $now);


        return false;
    }

    protected function warmup() : void
    {
        // Make shure the processing isn't fired twice
        $this->skipProcessing = true;

        $this->providePublicResources();
    }

    private function prepare(string $package, ?array $allowedExtensions, bool $allowDefaultGFX = true) : void
    {
        [$vendor, $packageName] = explode('\\', $package ?? __NAMESPACE__, 3);
        $extensionName = GeneralUtility::camelCaseToLowerCaseUnderscored($packageName);
        $this->packageNamespace = [ 'vendor' => $vendor, 'package' => $extensionName ];

        if (count($allowedExtensions))
        {
            array_walk($allowedExtensions, function(&$v) { return trim($v, " .\t\n\r\0\x0B"); });
            $this->allowedFileTypes = $allowedExtensions;
        }
        $this->configureAllowedMediaTypes(allowDefaultGFX: $allowDefaultGFX);

        $webAssetsPath = sprintf('%s/_assets', Environment::getPublicPath());
        if (!is_dir($webAssetsPath) && !file_exists($webAssetsPath))
        {
            mkdir($webAssetsPath);
        }

        $this->webAssetExtensionPath = sprintf('%s/%s/%s', $webAssetsPath, strtolower($vendor), $extensionName);
    }

    protected function configureAllowedMediaTypes(bool $allowDefaultGFX = true)
    {
        if ($allowDefaultGFX === true)
        {
            $this->allowedFileTypes += explode(',', $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'] ?? []);
        }
    }

    protected function checkIgnoredPattern(\SplFileInfo $file) : bool
    {
        foreach($this->ignoredPattern as $ignorePattern)
        {
            if (preg_match($ignorePattern, $file->getPathname()))
            {
                return true;
            }
        }

        return false;
    }

    protected function providePublicResources() : void
    {
        if (!$this->skip() === true)
        {
            return;
        }

        [, $package] = array_values($this->packageNamespace);
        $extensionKey = GeneralUtility::camelCaseToLowerCaseUnderscored($package);

        $extensionPath = ExtensionManagementUtility::extPath($extensionKey);
        $this->extensionResourcePublicPath = sprintf('%sResources/Public', $extensionPath);

        $publicFilesRecursiveIterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                $this->extensionResourcePublicPath,
                \FilesystemIterator::SKIP_DOTS & \FilesystemIterator::UNIX_PATHS
            ),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        /** @var \SplFileInfo $file */
        foreach($publicFilesRecursiveIterator as $file)
        {
            if ($file->getType() == 'dir')
            {
                continue;
            }

            if (str_starts_with($file->getBasename(), 'Extension.') && basename($file->getPath()) === 'Icons')
            {
                continue;
            }

            if ($this->checkIgnoredPattern($file))
            {
                continue;
            }

            self::createResourceLink(file: $file);
        }

        $this->registry->set($this->registryNamespace, 'is_cached', true);
    }

    private function createResourceLink(\SplFileInfo $file) : void
    {
        if (!$this->webAssetExtensionPath)
        {
            return;
        }

        $assetRelPath = PathUtility::getRelativePath($this->extensionResourcePublicPath, $file->getPath());
        $assetExtensionPath = sprintf('%s/%s', $this->webAssetExtensionPath, $assetRelPath);
        $assetFile = sprintf('%s/%s%s', $this->webAssetExtensionPath, $assetRelPath, $file->getBasename());

        if (file_exists($assetFile))
        {
            return;
        }

        if (is_dir($assetExtensionPath) || mkdir ($assetExtensionPath, 0777, true))
        {

            symlink($file->getPathname(), $assetFile);
        }
    }
}

