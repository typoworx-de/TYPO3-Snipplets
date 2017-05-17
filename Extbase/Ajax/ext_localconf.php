<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

// API Dispatcher using eID
$GLOBALS['TYPO3_CONF_VARS']['FE']['eID_include']['restapi'] = 'EXT:Vendor/MyExtensionName/Utility/Dispatcher.php';
