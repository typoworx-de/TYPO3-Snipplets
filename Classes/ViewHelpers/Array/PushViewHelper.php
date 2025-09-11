<?php
declare(strict_types=1);
namespace FooBar\FoobarExtension\ViewHelpers\Array;

use TYPO3\CMS\Extbase\Utility\DebuggerUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

class PushViewHelper extends AbstractViewHelper
{
    public function initializeArguments(): void
    {
        $this->registerArgument('add', 'mixed', 'Value to push to array', true);
        $this->registerArgument('prop', 'mixed', 'Array of key/value pairs', true);
        $this->registerArgument('as', 'string', 'store as variable', false);
    }

    public function render() :? string
    {
        $return = null;

        $as = $this->arguments['as'] ?? null;
        $add = $this->arguments['add'];

        if (is_string($this->arguments['prop']))
        {
            $as = $as ?? $this->arguments['prop'];
            $array = (array)$this->renderingContext->getVariableProvider()->get($this->arguments['prop']);
        }
        else
        {
            $array = &$this->arguments['prop'];
        }

        if (is_array($array))
        {
            $array[] = $add;

            $variableProvider = $this->renderingContext->getVariableProvider();

            if ($as !== null)
            {
                $variableProvider->add($as, $array);
                $return = $this->renderChildren();

                if ($return !== null)
                {
                    $variableProvider->remove($as);
                }
            }
        }

        return $return;
    }
}
