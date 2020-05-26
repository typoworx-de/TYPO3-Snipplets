<?php
namespace FooBar\FooBarExtension\ViewHelpers\Fluid\Uri;

use TYPO3\CMS\Extbase\Utility\DebuggerUtility;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;

/**
 * Class ImageViewHelper
 * Overrides Fluid f:image silencing the annoying exceptions
 *
 * @package FooBar\FooBarExtension\ViewHelpers\Fluid
 */
class ImageViewHelper extends \TYPO3\CMS\Fluid\ViewHelpers\Uri\ImageViewHelper
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
     * @return string
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
                DebuggerUtility::var_dump(
                    [
                        'ErrorMessage' => 'Error loading image!',
                        'Exception' => [
                            $e->getMessage(),
                            $e->getCode(),
                        ],
                        'Arguments' => $arguments,
                        'ControllerName' => $renderingContext->getControllerName(),
                        'TemplatePaths' => $renderingContext->getTemplatePaths()->getTemplateRootPaths(),
                    ],
                     __METHOD__
                );
            }
        }
    }
}
