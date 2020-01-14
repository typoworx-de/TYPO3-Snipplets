<?php
namespace Typoworx\FooBar\Utility;

use Composer\Autoload\ClassLoader;

/**
 * Class NamespaceUtility
 */
class NamespaceUtility
{
    /**
     * @param string $namespace     Class-Namespace (optional, if unset auto-detecting)
     * @return array|null
     */
    public static function getNamespace(string $namespace = __NAMESPACE__)
    {
        static $cachedNamespaces = [];

        if(!isset($cachedNamespaces[ $namespace ]))
        {
            $namespaceParts = [];
            if(preg_match_all('~(?<vendor>[^\\\]+)\\\(?<extension>[^\\\]+)\\\(Controller\\\(?<controller>[^\\\]+))?~', $namespace, $namespaceParts))
            {
                $cachedNamespaces[ $namespace ] = [
                    'vendor' => isset($namespaceParts['vendor'][0]) ? $namespaceParts['vendor'][0] : null,
                    'extension' => isset($namespaceParts['extension'][0]) ? $namespaceParts['extension'][0] : null,
                    'controller' => isset($namespaceParts['controller'][0]) ? $namespaceParts['controller'][0] : null,
                ];
            }
            else
            {
                $cachedNamespaces[ $namespace ] = null;
            }
        }

        return $cachedNamespaces[ $namespace ];
    }

    /**
     * @param string $namespace
     * @return array|null
     */
    public static function getVanishedNamespace(string $namespace = __NAMESPACE__)
    {
        $namespace = self::getNamespace($namespace);

        if(isset($namespace['controller']))
        {
            $namespace['controller'] = str_replace('Controller', '', $namespace['controller']);
        }

        return $namespace;
    }

    /**
     * @param string $namespace
     * @return string|null
     */
    public static function getVendorName(string $namespace) :? string
    {
        $namespace = self::getNamespace($namespace);

        return isset($namespace['vendor'])
            ? $namespace['vendor']
            : null
            ;
    }

    /**
     * @param string $namespace
     * @return string|null
     */
    public static function getExtensionKey(string $namespace) :? string
    {
        $namespace = self::getNamespace($namespace);

        return isset($namespace['extension'])
            ? $namespace['extension']
            : null
        ;
    }

    /**
     * @param string $extensionKey
     * @return string|null
     * @throws \TYPO3\CMS\Core\Exception
     */
    protected function getExtensionNamespacePrefix(string $extensionKey) :? string
    {
        static $extensionKeys;

        if($extensionKeys === null)
        {
            $extensionKeys = [];
        }

        $extensionKey = trim($extensionKey);
        if(empty($extensionKey))
        {
            return '';
        }

        if(isset($extensionKeys[ $extensionKey ]))
        {
            return $extensionKeys[ $extensionKey ];
        }

        /** @var ClassLoader $classLoader */
        $classLoader = Bootstrap::getInstance()->getEarlyInstance(ClassLoader::class);

        $result = array_filter($classLoader->getPrefixesPsr4(), function($item) use($extensionKey) {
            return strpos(current($item), $extensionKey) !== false;
        });

        $vendorPrefix = !empty($result) ? trim(key($result), '\ ') : null;
        $extensionKeys[ $extensionKey ] = $vendorPrefix;

        return $extensionKeys[ $extensionKey ];
    }
}
