<?php
namespace Typoworx\FooBar\Domain\Model\Json\Types;

/**
 * Interface JsonSerializeable
 *
 * Provides a Pre-Processor to convert an Object to JSON
 * by converting the essential properties to stdClass,
 * which can be handled by json_encode into JS-Object
 */
interface JsonSerializeable
{
    /**
     * @return mixed
     */
    public function toJson();
}
