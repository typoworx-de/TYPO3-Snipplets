<?php
namespace Foo\Bar\Backend;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility;

/**
 * @usage in TCA['ctrl']
 * 'label_userFunc' => \Cp\CpOpinionPoll\Backend\TcaUtility::class . '->formatLabel',
 * 'label_userFunc_options' => [
 *      'fields' => 'uid,crdate,score,rank_score',
 *      'format' => 'id %d, %s, score: %d, rank: %d',
 * ]
 */
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
