<?php
namespace ImageFORMAT\Romantica\Utility;

use \TYPO3\CMS\Core\Core\Bootstrap;
use \TYPO3\CMS\Core\Utility\ArrayUtility;
use \TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use \TYPO3\CMS\Core\Utility\GeneralUtility;
use \TYPO3\CMS\Frontend\Utility\EidUtility;

class AjaxDispatcher
{
    /**
     * @var $objectManager \TYPO3\CMS\Extbase\Object\ObjectManager
     * @inject
     */
    private $objectManager;



    /**
     * Main function of the class, will run the function call process.
     *
     * See class documentation for more information.
     */
    public function run()
    {
        if(empty($this->objectManager))
        {
            $this->objectManager = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
        }

        // Bootstrap initialization.
        Bootstrap::getInstance()
            ->initializeTypo3DbGlobal()
            ->loadCachedTca()
            ->initializeBackendUser()
        ;

        // Gets the Ajax call parameters.
        $arguments = (array)GeneralUtility::_GET();

        // Initializing TypoScript Frontend Controller.
        $id = (isset($arguments['id'])) ? $arguments['id'] : 0;
        $this->initializeTSFE($id);


        // Set which Extbase-Controller is used
        /*
        $dispatchController = [
            'vendorName'                    => 'ImageFORMAT',
            'extensionName'                 => 'Romantica',
            'pluginName'                    => 'Ajax',
            'controller'                    => 'Ajax',
            'action'                        => $arguments['action']
        ];
        */


        /**
         * Determine Plugin-Namespace (Vendor, Extension-Name)
         * as default Dispatcher-Values
         */
        try
        {
            if(empty($arguments['controller']))
            {
                header('HTTP/1.1 503 Service Unavailable');
                die('Dispatcher param "controller" missing!');
            }

            if(empty($arguments['action']))
            {
                header('HTTP/1.1 503 Service Unavailable');
                die('Dispatcher param "action" missing!');
            }

            if(preg_match('~(?<vendorName>[^\\\]*)\\\(?<extensionName>[^\\\]*)~', __NAMESPACE__, $namespace))
            {
                $dispatchController = [
                    'vendorName'                    => $namespace['vendorName'],
                    'extensionName'                 => $namespace['extensionName'],
                    'action'                        => $arguments['action']
                ];
            }

            $dispatchController['controller'] = ucFirst(GeneralUtility::underscoredToLowerCamelCase($arguments['controller']));

            if(empty($arguments['plugin']))
            {
                // Try fallback ...
                $dispatchController['pluginName'] = $dispatchController['controller'];
            }
            else
            {
                $dispatchController['pluginName'] = ucFirst(GeneralUtility::underscoredToLowerCamelCase($arguments['plugin']));
            }

            $dispatchController['switchableControllerActions'] = array(
                $dispatchController['controller'] => array($dispatchController['action'])
            );

            if(isset($arguments['arguments']))
            {
                $dispatchController['settings'] = $arguments['arguments'];
            }
        }
        catch(\Exception $e)
        {
            header('HTTP/1.1 503 Service Unavailable');
            die(sprintf('Internal Exception in %s on line %d', __CLASS__, __LINE__));
            //throw new \Exception(sprintf('Cannot determine default Vendor/Extension-Name in %s', __CLASS__));
        }

        //die(var_dump('<pre>', __NAMESPACE__, $dispatchController));


        /**
         * Build the request
         * made to the given Extbase Controller/Action
         */
        try
        {
            $bootstrap = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Core\\Bootstrap');
            $response = $bootstrap->run('', $dispatchController);

            ///die(var_dump('TRACE<pre>', $dispatchController, $response));
            echo $response;
        }
        catch(\Exception $e)
        {
            header('HTTP/1.1 503 Service Unavailable');
            ///die(var_dump('<pre>', $dispatchController, $e));
            die(sprintf('Internal Exception in %s on line %d', __CLASS__, __LINE__));
        }

/*
        $request = $this->objectManager->get('TYPO3\CMS\Extbase\Mvc\Request');
        $request->setControllerVendorName($dispatchController['vendorName']);
        $request->setControllerExtensionName($dispatchController['extensionName']);
        $request->setPluginName($dispatchController['pluginName']);
        $request->setControllerName($dispatchController['controller']);
        $request->setControllerActionName($dispatchController['action']);
        $request->setFormat(isset($arguments['format']) ? $arguments['format'] : 'html');

        unset($arguments['action'], $arguments['format']);
        $request->setArguments($arguments);

        /**
         * Build Response
         * by dispatched Request to Extbase-Controller/Action
         * /
        $response = $this->objectManager->get('TYPO3\CMS\Extbase\Mvc\ResponseInterface');
        $dispatcher = $this->objectManager->get('TYPO3\CMS\Extbase\Mvc\Dispatcher');
        $dispatcher->dispatch($request, $response);

        /** Just for Debugging! ** /
        //die(var_dump('<pre>', __METHOD__, $arguments, $response->getContent()));

        // Display the final result on screen.
        echo $response->getContent();
*/
    }


    /**
     * Initializes the $GLOBALS['TSFE'] Context
     *
     * @param int   $id     The if of the rootPage from which you want the controller to be based on.
     */
    private function initializeTSFE($id)
    {
        if(TYPO3_MODE !== 'FE')
        {
            return false;
        }

        $id = intval($id);

        /** @var \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController */
        //$GLOBALS['TSFE'] = $this->objectManager->get('TYPO3\\CMS\\Frontend\\Controller\\TypoScriptFrontendController', $GLOBALS['TYPO3_CONF_VARS'], $id, 0);
        $GLOBALS['TSFE'] = GeneralUtility::makeInstance(
            'TYPO3\\CMS\\Frontend\\Controller\\TypoScriptFrontendController',
            $GLOBALS['TYPO3_CONF_VARS'],
            $id,
            0,
            true
        );

        $GLOBALS['TSFE']->sys_page = GeneralUtility::makeInstance('TYPO3\CMS\Frontend\Page\PageRepository');
        $GLOBALS['TSFE']->cObj = $this->objectManager->get('TYPO3\\CMS\\Frontend\\ContentObject\\ContentObjectRenderer');

        $configurationManager = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Configuration\\ConfigurationManagerInterface');
        $configurationManager->setContentObject($GLOBALS['TSFE']->cObj);

        EidUtility::initLanguage();
        EidUtility::initTCA();

        // No Cache for Ajax stuff.
        $GLOBALS['TSFE']->set_no_cache();

        $GLOBALS['TSFE']->connectToDB();
        $GLOBALS['TSFE']->initFEuser();
        $GLOBALS['TSFE']->initUserGroups();
        $GLOBALS['TSFE']->checkAlternativeIdMethods();
        $GLOBALS['TSFE']->determineId();
        $GLOBALS['TSFE']->initTemplate();
        $GLOBALS['TSFE']->getPageAndRootline();
        $GLOBALS['TSFE']->getConfigArray();
        $GLOBALS['TSFE']->settingLanguage();
    }
}


// Start AjaxDispatcher::run
if(GeneralUtility::_GET('eID'))
{
    /** @var $ajaxDispatcher AjaxDispatcher*/
    $ajaxDispatcher = new AjaxDispatcher();
    $ajaxDispatcher->run();
}
