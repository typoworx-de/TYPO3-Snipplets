# Usage

Override in your Site-Package ext_localconf replacing Namespaces and Ext-Keys with yours:

```php
<?php
defined('TYPO3_MODE') or die();

call_user_func(function($extKey, $vendorName, $packageName)
    {
        /**
         * Apply some Fluid-Patches
         */
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][ \TYPO3\CMS\Fluid\ViewHelpers\ImageViewHelper::class ] = [
            'className' => \FooBar\FooBarExtension\ViewHelpers\Fluid\ImageViewHelper::class
        ];
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][ \TYPO3\CMS\Fluid\ViewHelpers\Uri\ImageViewHelper::class ] = [
            'className' => \FooBar\FooBarExtension\ViewHelpers\Fluid\Uri\ImageViewHelper::class
        ];
    },
    'your_extkey', 'YourNamespace', 'YourExtensionName'
);

```
