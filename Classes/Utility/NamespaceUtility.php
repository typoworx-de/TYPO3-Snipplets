<?php
namespace Typoworx\Foobar\Utility;

use TYPO3\CMS\Extbase\Utility\DebuggerUtility;

/**
 * Class NamespaceUtility
 * @package Typoworx\Foobar\Utility
 */
class NamespaceUtility
{
    /**
     * @param string $namespace         Class-Namespace        (optional, if unset auto-detecting)
     * @return array|null
     */
    public static function getNamespace(string $namespace = __NAMESPACE__)
    {
        static $cachedNamespaces = [];

        if(!isset($cachedNamespaces[ $namespace ]))
        {
            $namespaceParts = [];
            if(
                strpos($namespace, '\\Controller\\') !== false &&
                preg_match_all('~(?<vendor>[^\\\]+)\\\(?<extension>[^\\\]+)\\\(Controller\\\(?<controller>[^\\\]+))?~', $namespace, $namespaceParts))
            {
                $cachedNamespaces[ $namespace ] = [
                    'vendor' => isset($namespaceParts['vendor'][0]) ? $namespaceParts['vendor'][0] : null,
                    'extension' => isset($namespaceParts['extension'][0]) ? $namespaceParts['extension'][0] : null,
                    'controller' => isset($namespaceParts['controller'][0]) ? $namespaceParts['controller'][0] : null,
                ];
            }
            else             if(
                strpos($namespace, '\\Domain\\Model\\') !== false &&
                preg_match_all('~(?<vendor>[^\\\]+)\\\(?<extension>[^\\\]+)\\\Domain\\\Model\\\(?<model>[^\\\]+)?~', $namespace, $namespaceParts))
            {
                $cachedNamespaces[ $namespace ] = [
                    'vendor' => isset($namespaceParts['vendor'][0]) ? $namespaceParts['vendor'][0] : null,
                    'extension' => isset($namespaceParts['extension'][0]) ? $namespaceParts['extension'][0] : null,
                    'model' => isset($namespaceParts['model'][0]) ? $namespaceParts['model'][0] : null,
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
}
