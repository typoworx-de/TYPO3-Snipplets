<?php
namespace FooBar\FoobarExtension\ViewHelpers\Fluid;

use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;

/**
 * Class ImageViewHelper
 * Overrides Fluid f:image silencing the annoying exceptions
 *
 * @package FooBar\FoobarExtension\ViewHelpers\Fluid
 */
class ImageViewHelper extends \TYPO3\CMS\Fluid\ViewHelpers\ImageViewHelper
{
    /**
     * @var bool
     */
    public static $forceEnableDebug = false;

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
        $this->registerArgument('debug', 'bool', 'Explicitly allow Debugging-Messages', false, self::$forceEnableDebug);
        $this->registerArgument('throwException', 'bool', 'Explicitly allow PHP-Exceptions', false, self::$forceExcplicitExceptions);
    }

    /**
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param \TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface $renderingContext
     * @return mixed|string
     * @throws \Throwable
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        try
        {
            return parent::renderStatic($arguments, $renderChildrenClosure, $renderingContext);
        }
        catch (\Throwable $e)
        {
            if($arguments['throwException'] === true)
            {
                throw $e;
            }
            else if($arguments['debug'] === true || self::$forceEnableDebug === true)
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
