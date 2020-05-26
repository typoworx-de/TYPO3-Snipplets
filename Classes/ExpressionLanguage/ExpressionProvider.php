<?php
namespace FooBar\FooBarExtensionKey\ExpressionLanguage;

use TYPO3\CMS\Core\ExpressionLanguage\AbstractProvider;

/**
 * Class ConditionProvider
 * @package FooBar
 */
class ExpressionProvider extends AbstractProvider
{
    public function __construct()
    {
        $this->expressionLanguageProviders = [
            ExtensionLoadedConditionProvider::class
        ];
    }
}
