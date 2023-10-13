<?php
declare(strict_types=1);
namespace Foo\Bar\Utility;

use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ViewHelperCache
{
    private ?FrontendInterface $cache;

    public static string $cachePoolIdentifier = 'fwf_searchbar_viewhelpers';


    public function __construct(?CacheManager $cacheManager = null)
    {
        $cacheManager = $cacheManager ?? GeneralUtility::makeInstance(CacheManager::class);
        if($cacheManager?->hasCache(static::$cachePoolIdentifier))
        {
            $this->cache = $cacheManager?->getCache(static::$cachePoolIdentifier);
        }
    }

    public function hasCache() : bool
    {
        return $this->cache !== null;
    }

    public function set(string $key, mixed $value) : void
    {
        if (empty($key))
        {
            return;
        }

        $this->cache?->set($key, $value);
    }

    public function has(string $key) : bool
    {
        return $this->cache?->has($key);
    }

    public function get(string $key, mixed $default = null) : mixed
    {
        return $this->cache?->get($key) ?? $default;
    }
}
