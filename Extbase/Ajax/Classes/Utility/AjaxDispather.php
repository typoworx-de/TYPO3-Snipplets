<?php
namespace Typoworx\MyExtensionName\Utility;

use \TYPO3\CMS\Core\Core\Bootstrap;
use \TYPO3\CMS\Core\Utility\GeneralUtility;
use \TYPO3\CMS\Frontend\Utility\EidUtility;

class Dispatcher
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
        if (empty($this->objectManager))
        {
            $this->objectManager = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
        }

        // Bootstrap initialization.
        Bootstrap::getInstance()->initializeTypo3DbGlobal()->loadCachedTca()->initializeBackendUser();

        // Gets the Ajax call parameters.
        $arguments = (array)GeneralUtility::_GET();

        // Initializing TypoScript Frontend Controller.
        $id = (isset($arguments['id'])) ? $arguments['id'] : 0;

        // Initialize TSFE if given Page-ID or NULL
        $this->initializeTSFE($id);

        // Set which Extbase-Controller is used
        /*
        $dispatchController = [
            'vendorName'                    => 'VendorName',
            'extensionName'                 => 'ExtensionName',
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
            if (preg_match('~(?<vendorName>[^\\\]*)\\\(?<extensionName>[^\\\]*)~', __NAMESPACE__, $namespace))
            {
                $dispatchController = ['vendorName' => $namespace['vendorName'], 'extensionName' => $namespace['extensionName'], 'pluginName' => isset($arguments['plugin']) ? $arguments['plugin'] : 'Pi1', 'action' => $arguments['action']];
            }

            $dispatchController['controller'] = ucFirst(GeneralUtility::underscoredToLowerCamelCase($arguments['controller']));
            if (!empty($arguments['plugin']))
            {
                $dispatchController['pluginName'] = ucFirst(GeneralUtility::underscoredToLowerCamelCase($arguments['plugin']));
            }

            if (isset($arguments['arguments']))
            {
                $dispatchController['settings'] = $arguments['arguments'];
            }

            $pluginConfiguration = $this->getPluginConfiguration($dispatchController);

            if(isset($pluginConfiguration))
            {
                $defaultController = key($pluginConfiguration);
                if (empty($arguments['controller']))
                {
                    $dispatchController['controller'] = $defaultController;
                }

                //$defaultActions = $pluginConfiguration[ $dispatchController['controller'] ]['actions'];

                $defaultActions = $this->getPluginConfiguration($dispatchController, $dispatchController['controller']);

                if (empty($arguments['action']))
                {
                    $dispatchController['action'] = isset($defaultActions) ? $defaultActions[0] : '';
                }

                $dispatchController['switchableControllerActions'] = [
                    $dispatchController['controller'] => $defaultActions
                ];
            }
            else
            {
                $dispatchController['switchableControllerActions'] = [
                    $dispatchController['controller'] => [ $dispatchController['action'] ]
                ];
            }
        }
        catch (\Exception $e)
        {
            header('HTTP/1.1 503 Service Unavailable');
            //die(sprintf('Internal Exception in %s on line %d', __CLASS__, __LINE__));

            if (!\TYPO3\CMS\Core\Utility\GeneralUtility::getApplicationContext()->isProduction())
            {
                throw new \Exception(sprintf('Cannot determine default Vendor/Extension-Name in %s', __CLASS__));
            }
        }
        ///die(var_dump('<pre>', __METHOD__, $dispatchController));

        /**
         * Build the request
         * made to the given Extbase Controller/Action
         */
        try
        {
            if (empty($arguments['id']))
            {
                /**
                 * Attention:
                 * Currently a lot of TypoScript-Setup is missing at this point!
                 *
                 * f.e. Extbase Table-Mappings from TypoScript are unknown!
                 */

                /** @var \TYPO3\CMS\Extbase\Mvc\Web\Request $request */
                $requestDispatch = $this->objectManager->get('TYPO3\CMS\Extbase\Mvc\Web\Request');

                $requestDispatch->setControllerVendorName($dispatchController['vendorName']);
                $requestDispatch->setControllerExtensionName($dispatchController['extensionName']);
                $requestDispatch->setPluginName($dispatchController['pluginName']);
                $requestDispatch->setControllerName($dispatchController['controller']);
                $requestDispatch->setControllerActionName($dispatchController['action']);
                $requestDispatch->setFormat(isset($arguments['format']) ? $arguments['format'] : 'html');

                $requestDispatch->setRequestUri(GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL'));
                $requestDispatch->setBaseUri(GeneralUtility::getIndpEnv('TYPO3_SITE_URL'));
                $requestDispatch->setMethod((isset($_SERVER['REQUEST_METHOD'])) ? $_SERVER['REQUEST_METHOD'] : null);

                /** @var \TYPO3\CMS\Extbase\Mvc\Web\Response $responseObject */
                $responseObject = $this->objectManager->get('TYPO3\CMS\Extbase\Mvc\Web\Response');

                /** @var \TYPO3\CMS\Extbase\Mvc\Dispatcher $dispatcher */
                $dispatcher = $this->objectManager->get('TYPO3\CMS\Extbase\Mvc\Dispatcher');
                $dispatcher->dispatch($requestDispatch, $responseObject);

                $response = $responseObject->getContent();
                $responseObject->shutdown();
            }
            else
            {
                // Dispatch using Bootstrap with Page-Uid
                $bootstrap = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Core\\Bootstrap');
                $response = $bootstrap->run('', $dispatchController);
            }

            ///die(var_dump(__METHOD__,'TRACE<pre>', $dispatchController, $response));
            echo $response;
        }
        catch (\Exception $e)
        {
            header('HTTP/1.1 503 Service Unavailable');

            if (!\TYPO3\CMS\Core\Utility\GeneralUtility::getApplicationContext()->isProduction())
            {
                throw($e);
            }
            else
            {
                die('API is temporarely unavailable');
            }
        }
    }

    /**
     * Initializes the $GLOBALS['TSFE'] Context
     *
     * @param int $id The if of the rootPage from which you want the controller to be based on.
     * @return bool
     */
    private function initializeTSFE($id = null)
    {
        if (TYPO3_MODE !== 'FE')
        {
            return false;
        }
        $id = intval($id);

        /** @var \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController */
        $GLOBALS['TSFE'] = $this->objectManager->get('TYPO3\\CMS\\Frontend\\Controller\\TypoScriptFrontendController', $GLOBALS['TYPO3_CONF_VARS'], $id, 0);
        $GLOBALS['TSFE'] = GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\Controller\\TypoScriptFrontendController', $GLOBALS['TYPO3_CONF_VARS'], $id, 0, true);
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

        if(!empty($id))
        {
            $GLOBALS['TSFE']->checkAlternativeIdMethods();
            $GLOBALS['TSFE']->determineId();
            $GLOBALS['TSFE']->getPageAndRootline();
            $GLOBALS['TSFE']->initTemplate();
            $GLOBALS['TSFE']->getConfigArray();
        }

        $GLOBALS['TSFE']->settingLanguage();
    }

    /**
     * @param $dispatchController
     * @param bool $resolveActions
     * @return array
     */
    protected function getPluginConfiguration($dispatchController, $resolveActions = false)
    {
        if(isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['extensions'][ $dispatchController['extensionName'] ]['plugins'][ $dispatchController['pluginName'] ]['controllers'][ $dispatchController['controller'] ]))
        {

            if ($resolveActions == true)
            {
                if (isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['extensions'][$dispatchController['extensionName']]['plugins'][$dispatchController['pluginName']]['controllers'][$dispatchController['controller']]))
                {
                    return (array)$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['extensions'][$dispatchController['extensionName']]['plugins'][$dispatchController['pluginName']]['controllers'][$dispatchController['controller']]['actions'];
                }
            }
            else
            {
                return (array)$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['extensions'][$dispatchController['extensionName']]['plugins'][$dispatchController['pluginName']]['controllers'];
            }
        }

        return [];
    }
}


// Start Dispatcher::run
if(GeneralUtility::_GET('eID'))
{
    /** @var $Dispatcher Dispatcher*/
    $dispatcher = new Dispatcher();
    $dispatcher->run();
}
