<?php
declare(strict_types=1);
namespace Foo\Bar\ViewHelpers\Object;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;
use TYPO3Fluid\Fluid\ViewHelpers\RenderViewHelper;
use Foo\Bar\Utility\ViewHelperCache;

/**
 * @usage in Fluid
 * <ifsb:object.create
 *   as="localisation"
 *   object="{
 *   \"date\": {
 *     \"format\": \"{f:translate(key: 'localisation.date.format', default: '%d-%m-%Y')}\",
 *   },
 *   \"currency\": {
 *     \"prependCurrency\": \"{f:translate(key: 'localisation.currency.prependCurrency', default: 'false')}\",
 *       \"separator\": {
 *         \"decimal\": \"{f:translate(key: 'localisation.currency.decimal', default: ',')}\",
 *         \"thousands\": \"{f:translate(key: 'localisation.currency.thousands', default: '.')}\"
 *       }
 *     }
 *   }"
 * />
 */
class CreateViewHelper extends RenderViewHelper
{
    use CompileWithRenderStatic;

    public function initializeArguments()
    {
        parent::initializeArguments();

        $this->registerArgument('object', 'mixed', 'Input Object"', true);
        $this->registerArgument('as', 'string', 'output as');
        $this->registerArgument('cache', 'bool', 'enable Caching)"');
        $this->registerArgument('cacheKey', 'string', 'cache key (alternatively \'as\'-key is used');
    }

    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext) : mixed
    {
        $return = null;
        $object = $arguments['object'] ?? null;
        $as = $arguments['as'];

        $cacheKey = $arguments['cacheKey'] ?? $as;
        $cache = $arguments['cache'] ?? true;

        $viewHelperCache = GeneralUtility::makeInstance(ViewHelperCache::class);

        if ($cache === true && $cacheKey !== null && empty($cacheKey))
        {
            if ($viewHelperCache->has($cacheKey))
            {
                return $viewHelperCache->get($cacheKey);
            }
        }

        try
        {
            if (is_string($object))
            {
                $return = json_decode($object);
            }
            else if (is_array($object))
            {
                $return = $object;
            }
        }
        catch (\JSON_THROW_ON_ERROR $e)
        {
            $return = new \stdClass();
            $return->{'jsonError'} = true;
        }

        if ($cache === true && $cacheKey !== null && empty($cacheKey))
        {
            $viewHelperCache->set($cacheKey, $return);
        }

        if ($as !== null)
        {
            $renderingContext->getVariableProvider()->add($as, $return);
            $return = null;
        }

        return $return;
    }
}
