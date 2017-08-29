<?php
namespace Typoworx\MyTemplate\Provider;

use \DmitryDulepov\Realurl\Configuration\AutomaticConfigurator;

/**
 * Class RealUrlConfiguration
 * @package Keeen\Fluidtemplates\Provider
 */
class RealUrlConfiguration
{
    /**
     * @param array $params
     * @param \DmitryDulepov\Realurl\Configuration\AutomaticConfigurator $automaticConfigurator
     * @return array
     */
    public function addConfiguration(array $params, AutomaticConfigurator $automaticConfigurator)
    {
        $defaultConfiguration = [
            'preVars' => [
                0 => [
                    'GETvar' => 'L',
                    'valueMap' => [
                        'en' => '0',
                    ],
                    'noMatch' => 'bypass'
                ],
                [
                    'GETvar' => 'no_cache',
                    'valueMap' => [
                        'nc' => 1,
                    ],
                    'noMatch' => 'bypass',
                ],
            ]
        ];

        foreach($params['config'] as $domain => $configuration)
        {
            $params['config'][$domain] = array_replace_recursive($configuration, $defaultConfiguration);
        }

        return $params;
    }
}
