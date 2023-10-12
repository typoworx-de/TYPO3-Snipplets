<?php
declare(strict_types=1);
namespace Foo\Bar\ViewHelpers\Form;

use TYPO3\CMS\Fluid\Core\Rendering\RenderingContext;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;
use Foo\Bar\Utility\FrontendUtility;

class GetContentObjectViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    public function initializeArguments()
    {
        parent::initializeArguments();

        $this->registerArgument('as', 'string', 'provide settings as variable', false, 'currentContentObject');
    }

    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface|RenderingContext $renderingContext) : mixed
    {
        $data = FrontendUtility::instance()->getContentObject($renderingContext)?->data;

        $return = null;
        if ($arguments['as'] !== null)
        {
            $variableProvider = $renderingContext->getVariableProvider();
            $variableProvider->add($arguments['as'], $data);

            /** @var null|string $return will be null if the ViewHelper-Node has no Children! */
            $return = $renderChildrenClosure();

            if ($return === null)
            {
                return null;
            }

            $variableProvider->remove($arguments['as']);
        }

        return $return ?? $data;
    }
}
