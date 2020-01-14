<?php
namespace Typoworx\FooBar\Utility;

use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Http\ServerRequestFactory;

/**
 * Class ObjectManager
 */
class StaticObjectManager implements SingletonInterface
{
    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManager
     */
    private static $objectManager;

    /**
     * @var \TYPO3\CMS\Core\Http\ServerRequest
     */
    private static $serverRequest;


    /**
     * @return \TYPO3\CMS\Extbase\Object\ObjectManager
     */
    public static function getInstance()
    {
        if(empty(self::$objectManager))
        {
            self::$objectManager = new ObjectManager();
        }

        return self::$objectManager;
    }

    /**
     * Returns a fresh or existing instance of the object specified by $objectName.
     *
     * @param string $objectName The name of the object to return an instance of
     * @param mixed ...arguments
     * @return object The object instance
     * @api
     */
    public static function get($objectName, ...$arguments)
    {
        $arguments = func_get_args();

        if(count($arguments))
        {
            return call_user_func_array([ self::getInstance(), 'get'], $arguments);
        }
        else
        {
            return self::getInstance()->get($objectName);
        }
    }

    /**
     * Returns an empty object-instance specified by $objectName.
     *
     * @param string $objectName The name of the object to return an instance of
     * @param mixed ...arguments
     * @return object The object instance
     * @api
     */
    public static function getEmptyObject($objectName, ...$arguments)
    {
        $arguments = func_get_args();

        if(count($arguments))
        {
            return call_user_func_array([ self::getInstance(), 'getEmptyObject'], $arguments);
        }
        else
        {
            return self::getInstance()->getEmptyObject($objectName);
        }
    }

    /**
     * @return \TYPO3\CMS\Core\Http\ServerRequest
     */
    public static function getServerRequest() : ServerRequest
    {
        if(self::$serverRequest === null)
        {
            self::$serverRequest = ServerRequestFactory::fromGlobals();
        }

        return self::$serverRequest;
    }
}
