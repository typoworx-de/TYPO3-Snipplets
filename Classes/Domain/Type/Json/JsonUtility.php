<?php
namespace Typoworx\FooBar\Domain\Model\Json;

use Typoworx\FooBar\Domain\Model\Json\JsonSerializeable;

/**
 * Class JsonUtility
 */
class JsonUtility
{
    /**
     * @param object|array $object
     * @return mixed
     */
    public static function toJsonObject($object)
    {
        if(!is_object($object) && !is_array($object) && !is_iterable($object))
        {
            return $object;
        }

        if(is_object($object))
        {
            $object = clone $object;
        }

        if(is_iterable($object))
        {
            foreach($object as $index => $item)
            {
                if($item instanceof JsonSerializeable)
                {
                    $item = $item->toJson();

                    if(is_array($object))
                    {
                        $object[ $index ] = $item;
                    }
                    else if($object instanceof \ArrayAccess)
                    {
                        $object->offsetSet($index, $item);
                    }
                }
            }
        }

        return $object;
    }

    /**
     * @param $object
     * @param string $className
     * @return object
     */
    public static function castObject($object, string $className)
    {
        $serializedStdClass = serialize($object);
        $serializedStdClass = str_replace(
            'O:8:"stdClass"',
            sprintf('O:%d:"%s"', strlen($className), $className),
            $serializedStdClass
        );

        return unserialize($serializedStdClass);
    }
}
