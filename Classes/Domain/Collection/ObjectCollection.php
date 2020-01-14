<?php
namespace Typoworx\FooBar\Domain\Collection;

use Typoworx\FooBar\Domain\Model\Json\Types\JsonSerializeable;

/**
 * Class ObjectCollection
 */
class ObjectCollection extends \ArrayObject implements JsonSerializeable
{
    public function getFirst()
    {
        return array_shift($this->getArrayCopy());
    }

    public function getLast()
    {
        return array_pop($this->getArrayCopy());
    }

    /**
     * Converts the current Iterator-Array into Array with numeric Keys
     * f.e. ['foo' => 'bar'] will become [0 => 'bar']
     */
    public function convertToNumericalIndex() : void
    {
        if($this->count() === 0)
        {
            return;
        }

        $numericIndexedArray = [];
        foreach($this->getIterator() as $item)
        {
            $numericIndexedArray[] = $item;
        }

        $this->exchangeArray($numericIndexedArray);
    }

    /**
     * @return \stdClass
     */
    public function toJson()
    {
        $clone = $this;

        foreach($clone->getIterator() as $index => $item)
        {
            if($item instanceof JsonSerializeable)
            {
                $clone->offsetSet($index, $item->toJson());
            }
        }

        return $clone->getArrayCopy();
    }
}
