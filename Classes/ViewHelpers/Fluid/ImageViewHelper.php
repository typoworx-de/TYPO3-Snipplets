<?php
namespace FooBar\FooBarExtension\ViewHelpers\Fluid;

use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;

/**
 * Class ImageViewHelper
 * Overrides Fluid f:image silencing the annoying exceptions
 *
 * @package FooBar\FooBarExtension\ViewHelpers\Fluid
 */
class ImageViewHelper extends \TYPO3\CMS\Fluid\ViewHelpers\ImageViewHelper
{
    /**
     * @var bool
     */
    public static $forceExcplicitExceptions = false;


    /**
     * Initialize arguments.
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('throwException', 'bool', 'Explicitly allow PHP-Exceptions', false, self::$forceExcplicitExceptions);
    }

    /**
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param \TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface $renderingContext
     * @return mixed|string
     * @throws \Exception
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        try
        {
            return parent::renderStatic($arguments, $renderChildrenClosure, $renderingContext);
        }
        catch (\Exception $e)
        {
            if($arguments['throwException'] === true)
            {
                throw $e;
            }
            else
            {
                return sprintf(
                    '<span class="image-exception" data-error-message="%s" data-error-code="%d">Error loading image!</span>',
                    $e->getMessage(),
                    $e->getCode()
                );
            }
        }
    }
}
