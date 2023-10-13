<?php
declare(strict_types=1);
namespace Foo\Bar\ViewHelpers\Format;

use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Fluid\ViewHelpers\Format\DateViewHelper as FluidDateViewHelper;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;

/**
 * @usage in Fluid
 * Simply use exactly like <f:format.date />
 */
class DateViewHelper extends FluidDateViewHelper
{
    public function initializeArguments()
    {
        parent::initializeArguments();
        unset($this->argumentDefinitions['format']);

        $this->registerArgument('format', 'string', 'Format String which is taken to format the Date/Time', false, null);
    }

    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext) :? string
    {
        if ($arguments['format'] === null)
        {
            $arguments['format'] = LocalizationUtility::translate(
                key: 'localisation.date.format',
                extensionName: 'fwf_searchbar'
            );
        }

        return parent::renderStatic($arguments, $renderChildrenClosure, $renderingContext);
    }
}
