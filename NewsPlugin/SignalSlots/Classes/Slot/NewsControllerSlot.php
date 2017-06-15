<?php
namespace Typoworx\MyPluginName\Slots;

use \TYPO3\CMS\Extbase\Persistence\Generic\QueryResult;
use \GeorgRinger\News\Domain\Model\Dto\NewsDemand;

use TYPO3\CMS\Extbase\Utility\DebuggerUtility;

class NewsControllerSlot
{
    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManager
     * @inject
     */
    protected $objectManager;

    /**
     * ListAction SignalSlot
     *
     * @param \TYPO3\CMS\Extbase\Persistence\Generic\QueryResult|null $news
     * @param $overrideDemand
     * @param \GeorgRinger\News\Domain\Model\Dto\NewsDemand $demand
     * @param array $assignedValues
     * @return array
     */
    public function listAction(QueryResult $news = null, $overrideDemand, NewsDemand $demand, $assignedValues = [])
    {
        return [$news, $overrideDemand, $demand, $assignedValues];
        //DebuggerUtility::var_dump($overrideDemand);
    }
}
