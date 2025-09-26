<?php
declare(strict_types=1);
namespace Foo\Bar\ViewHelpers\Image\Traits;

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Resource\Exception\InvalidFileException;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Extbase\Domain\Model\FileReference as ExtbaseFileReference;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3Fluid\Fluid\Core\ViewHelper\ArgumentDefinition;

trait ImageHelperTraits
{
    protected function forkArgumentsFrom(string $finalCoreClass) : bool
    {
        // Hack to fetch initializeArguments() from other Core-ViewHelper Class (that may has become final & non-extendable)
        try
        {
            $ivh = GeneralUtility::makeInstance($finalCoreClass);
            $reflection = new \ReflectionClass($ivh);
            $reflection
                ->getMethod('initializeArguments')
                ?->invoke($ivh)
            ;
  
            $argumentDefinitions = $reflection->getProperty('argumentDefinitions');
            $argumentDefinitions?->setAccessible(true);
  
            $this->argumentDefinitions = array_merge(
                $argumentDefinitions?->getValue($ivh) ?? [],
                $this->argumentDefinitions
            );
  
            return true;
        }
        catch (\Exception $e)
        {
            if (!Environment::getContext()->isProduction())
            {
                throw $e;
            }
        }
  
        return false;
    }
  
    protected function overrideArgument($name, $type, $description, $required = false, $defaultValue = null, $escape = null, ?string $before = null)
    {
        if ($this->argumentDefinitions[$name] ?? null)
        {
            unset($this->argumentDefinitions[$name]);
        }
  
        $this->registerArgument($name, $type, $description, $required, $defaultValue, $escape);
  
        if ($before !== null)
        {
            $keys = array_keys($this->argumentDefinitions);
  
            if (in_array($before, $keys, true))
            {
                $pos = array_search($before, $keys, true);
  
                $before = array_slice($this->argumentDefinitions, 0, $pos, true);
                $current = $this->argumentDefinitions[ $name ];
                $after  = array_slice($this->argumentDefinitions, $pos, null, true);
                unset($this->argumentDefinitions[$name]);
  
                $this->argumentDefinitions = $before + [ $name => $current ] + $after;
            }
        }
  
        return $this;
    }
}
