<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/realurl/class.tx_realurl_autoconfgen.php']['postProcessConfiguration']['_DEFAULT_'] = 'EXT:mytemplateext/Classes/Provider/RealUrlConfiguration.php:Typoworx\\MyTemplate\\Provider\\RealUrlConfiguration->addConfiguration';

