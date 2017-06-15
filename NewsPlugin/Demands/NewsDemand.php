<?php
namespace Typoworx\MyPluginName\Hooks;

use \TYPO3\CMS\Extbase\Utility\DebuggerUtility;
use \GeorgRinger\News\Domain\Repository\NewsRepository;

class NewsDemand
{
    public function modify(array $params, NewsRepository $newsRepository)
    {
        DebuggerUtility::var_dump($params);
        die(var_dump(__METHOD__));
    }
}
