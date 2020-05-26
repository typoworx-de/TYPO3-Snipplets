<?php
namespace FooBar\FooBarExtensionKey\ExpressionLanguage;

use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

/**
 * Class ExtensionLoadedConditionProvider
 * @package FooBar
 *
 * @usage TypoScript
 * [ extensionLoaded('any-extension-name') ]
 */
class ExtensionLoadedConditionProvider implements ExpressionFunctionProviderInterface
{
    /**
     * @return ExpressionFunction[] An array of Function instances
     */
    public function getFunctions(): array
    {
        return [
            $this->getExtensionLoadedFunction()
        ];
    }

    /**
     * Provides Symfony Expression-Condition 'extensionLoaded'
     * @return \Symfony\Component\ExpressionLanguage\ExpressionFunction
     */
    protected function getExtensionLoadedFunction(): ExpressionFunction
    {
        return new ExpressionFunction('extensionLoaded', function () {
            // Not implemented, we only use the evaluator
        }, function ($arguments, $extKey) {
            return ExtensionManagementUtility::isLoaded($extKey);
        });
    }
}
