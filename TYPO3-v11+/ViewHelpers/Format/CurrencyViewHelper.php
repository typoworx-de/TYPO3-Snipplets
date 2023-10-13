<?php
declare(strict_types=1);
namespace Foo\Bar\ViewHelpers\Format;

use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Fluid\ViewHelpers\Format\CurrencyViewHelper as FluidCurrencyViewHelper;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;

/**
 * @usage in Fluid
 * Simply use exactly like <f:format.currency />
 */
class CurrencyViewHelper extends FluidCurrencyViewHelper
{
    public function initializeArguments()
    {
        parent::initializeArguments();

        unset(
            $this->argumentDefinitions['currencySign'],
            $this->argumentDefinitions['decimalSeparator'],
            $this->argumentDefinitions['thousandsSeparator'],
            $this->argumentDefinitions['prependCurrency'],
            $this->argumentDefinitions['separateCurrency'],
            $this->argumentDefinitions['decimals']
        );

        $this->registerArgument('currencySign', 'string', 'The currency sign, eg $ or €.', false, null);
        $this->registerArgument('decimalSeparator', 'string', 'The separator for the decimal point.', false, null);
        $this->registerArgument('thousandsSeparator', 'string', 'The thousands separator.', false, null);
        $this->registerArgument('prependCurrency', 'bool', 'Select if the currency sign should be prepended', false, null);
        $this->registerArgument('separateCurrency', 'bool', 'Separate the currency sign from the number by a single space, defaults to true due to backwards compatibility', false, null);
        $this->registerArgument('decimals', 'int', 'Set decimals places.', false, null);
    }

    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext) :? string
    {
        static::localizeArgument('localisation.currency.currencySign', $arguments['currencySign']);
        static::localizeArgument('localisation.currency.decimals', $arguments['decimals'], 2, 'int');
        static::localizeArgument('localisation.currency.prependCurrency', $arguments['prependCurrency'], false, 'bool');
        static::localizeArgument('localisation.currency.separateCurrency', $arguments['separateCurrency'], true, 'bool');
        static::localizeArgument('localisation.currency.separator.decimal', $arguments['decimalSeparator'], ',');
        static::localizeArgument('localisation.currency.separator.thousands', $arguments['thousandsSeparator'], '.');

        return parent::renderStatic($arguments, $renderChildrenClosure, $renderingContext);
    }

    protected static function localizeArgument(string $key, mixed &$argument, mixed $default = null, ?string $cast = null) : void
    {
        if ($argument !==null || empty($key))
        {
            return;
        }

        $argument = LocalizationUtility::translate(key: $key, extensionName: 'fwf_searchbar') ?? $default;

        if ($cast !== null)
        {
            settype($argument, $cast);
        }
    }
}
