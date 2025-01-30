<?php
declare(strict_types=1);
namespace FooBar\FoobarExtension\\ViewHelpers;

use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\ViewHelperNode;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\ViewHelpers\RenderViewHelper as CoreRenderViewHelper;
use TYPO3Fluid\Fluid\ViewHelpers\SectionViewHelper;

class RenderViewHelper extends CoreRenderViewHelper
{
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        $renderedChildNodes = $renderChildrenClosure();

        $viewHelpervariableProvider = $renderingContext->getViewHelperVariableContainer();
        $viewHelpervariableProvider->add(self::class, 'renderedChildNodes', $renderedChildNodes);

        $return = parent::renderStatic($arguments, $renderChildrenClosure, $renderingContext);

        $viewHelpervariableProvider->remove(self::class, 'renderedChildNodes');

        return $return;
    }

    protected function buildRenderChildrenClosure()
    {
        return function ()
        {
            $this->renderingContextStack[] = $this->renderingContext;
            $renderedNodes = [];
            foreach($this->childNodes as $childNode)
            {
                if ($childNode instanceof ViewHelperNode)
                {
                    if ($childNode->getUninitializedViewHelper() instanceof SectionViewHelper)
                    {
                        $sectionName = $childNode->getArguments()['name']?->getText();
                        $renderedNodes[ $sectionName ] = $childNode->evaluateChildNodes($this->renderingContext);
                    }
                }
            }

            $this->setRenderingContext(array_pop($this->renderingContextStack));

            return $renderedNodes;
        };
    }
}
