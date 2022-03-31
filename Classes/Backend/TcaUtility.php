<?php
namespace Foo\Bar\Backend;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility;

class TcaUtility
{
    public function formatLabel(&$parameters)
    {
        if($parameters['options']['fields'])
        {
            $fields = GeneralUtility::trimExplode(',', $parameters['options']['fields']) ?? null;
            foreach($fields as $index => $field)
            {
                $value = BackendUtility::getProcessedValue(
                    $parameters['table'],
                    $field,
                    $parameters['row'][ $field ],
                    30,
                    true
                );

                $fields[ $index ] = $value;
            }

            if(!!$parameters['options']['format'])
            {
                $parameters['title'] = vsprintf($parameters['options']['format'], $fields);
            }
            else
            {
                $parameters['title'] = implode(', ', $fields);
            }
        }
    }
}
