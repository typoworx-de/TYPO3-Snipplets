<?php
if (!defined('TYPO3_MODE')) {
    die ('Access denied.');
}

/** @var \TYPO3\CMS\Extbase\SignalSlot\Dispatcher $signalSlotDispatcher */
$signalSlotDispatcher = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\SignalSlot\\Dispatcher');
$signalSlotDispatcher->connect(
    'GeorgRinger\\News\\Controller\\NewsController',
    \GeorgRinger\News\Controller\NewsController::SIGNAL_NEWS_LIST_ACTION,
    'Typoworx\\MyPluginName\\Slots\\NewsControllerSlot',
    'listAction',
    TRUE
);

$GLOBALS['TYPO3_CONF_VARS']['EXT']['news']['Domain/Repository/AbstractDemandedRepository.php']['findDemanded'][$_EXTKEY] = 'Typoworx\\MyPluginName\\Hooks\\NewsDemand->modify';
