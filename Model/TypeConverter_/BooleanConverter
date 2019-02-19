<?php
namespace Typoworx\FooBar\Domain\TypeConverter;

use TYPO3\CMS\Extbase\Utility\DebuggerUtility;

/**
 * Class BooleanConverter
 * @package Typoworx\FooBar\Domain\TypeConverter
 */
class BooleanConverter extends \TYPO3\CMS\Extbase\Property\TypeConverter\BooleanConverter
{
    /**
     * @var array<string>
     */
    protected $sourceTypes = ['boolean', 'integer', 'string'];

    /**
     * @var string
     */
    protected $targetType = 'boolean';

    /**
     * @var int
     */
    protected $priority = 15;


    /**
     * Actually convert from $source to $targetType
     *
     * @param string $source
     * @param string $targetType
     * @param array $convertedChildProperties
     * @param \TYPO3\CMS\Extbase\Property\PropertyMappingConfigurationInterface $configuration
     * @return bool
     * @api
     */
    public function convertFrom($source, $targetType, array $convertedChildProperties = [], \TYPO3\CMS\Extbase\Property\PropertyMappingConfigurationInterface $configuration = null)
    {
        $value = false;
        if(is_bool($source))
        {
            $value = $source;
        }
        else if(is_int($source))
        {
            $value = $source === 1;
        }
        else if($source === null)
        {
            $value = false;
        }
        else if(is_string($source))
        {
            $source = trim($source);

            if($source === 'true' || $source === 'false')
            {
                $value = $source === 'true';
            }
            else
            {
                $value = !empty($source);
            }
        }

        ///DebuggerUtility::var_dump(['Source' => $source, 'Target' => $targetType], __METHOD__);
        ///DebuggerUtility::var_dump($value, __METHOD__);

        return (bool)$value;
    }
}
