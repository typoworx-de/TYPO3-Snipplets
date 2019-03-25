<?php
namespace Typoworx\FooBar\Utility;

use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Frontend\Utility\EidUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

/**
 * Class Dispatcher
 * @package Typoworx\FooBar\Utility
 * @description
 * to be initialized like this in ext_localconf.php:
 * > $GLOBALS['TYPO3_CONF_VARS']['FE']['eID_include']['test'] = \Typoworx\FooBar\Utility\Dispatcher::class . '::run';
 */
class Dispatcher
{
    /**
     * @var $objectManager \TYPO3\CMS\Extbase\Object\ObjectManager
     * @inject
     */
    protected $objectManager;

    /**
     * @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface
     */
    protected $configurationManager;


    /**
     * Main function of the class, will run the function call process.
     *
     * See class documentation for more information.
     */
    public function run()
    {
        if (empty($this->objectManager))
        {
            $this->objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        }

        // Bootstrap initialization.
        Bootstrap::getInstance()
            ->initializeTypo3DbGlobal()
            ->initializeBackendUser()
        ;

        // Gets the Ajax call parameters.
        $arguments = (array)GeneralUtility::_GET();

        // Initializing TypoScript Frontend Controller.
        $id = (isset($arguments['id'])) ? $arguments['id'] : 0;

        // Initialize TSFE if given Page-ID or NULL
        $this->initializeTSFE($id);

        /**
         * Optionally
         * Initialize Frontend.
         * This is needed for LinkBuilder and TypoLink!
         */
        if(empty($GLOBALS['TSFE']->tmpl))
        {
            $GLOBALS['TSFE']->initTemplate();
        }

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
                $dispatchController = [
                    'vendorName' => $namespace['vendorName'],
                    'extensionName' => isset($arguments['extension']) ? $arguments['extension'] : $namespace['extensionName'],
                    'pluginName' => isset($arguments['plugin']) ? $arguments['plugin'] : 'Pi1',
                    'action' => $arguments['action']
                ];
            }

            if (!empty($arguments['vendor']))
            {
                $dispatchController['vendorName'] = strpos($arguments['vendor'], '_') === false
                    ? $arguments['vendor']
                    : GeneralUtility::underscoredToLowerCamelCase($arguments['vendor'])
                ;

                $dispatchController['vendorName'] = ucFirst($dispatchController['vendorName']);
            }

            if (!empty($arguments['extension']))
            {
                $dispatchController['extensionName'] = strpos($arguments['extension'], '_') === false
                    ? $arguments['extension']
                    : GeneralUtility::underscoredToLowerCamelCase($arguments['extension'])
                ;

                $dispatchController['extensionName'] = ucFirst($dispatchController['extensionName']);
            }

            if (!empty($arguments['controller']))
            {
                $dispatchController['controller'] = strpos($arguments['controller'], '_') === false
                    ? $arguments['controller']
                    : GeneralUtility::underscoredToLowerCamelCase($arguments['controller'])
                ;

                $dispatchController['controller'] = ucFirst($dispatchController['controller']);
            }

            if (!empty($arguments['plugin']))
            {
                $dispatchController['pluginName'] = strpos($arguments['plugin'], '_') === false
                    ? $arguments['plugin']
                    : GeneralUtility::underscoredToLowerCamelCase($arguments['plugin'])
                ;

                $dispatchController['pluginName'] = ucFirst($dispatchController['pluginName']);
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

            // Initialize ConfigurationManager
            /** @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface $configurationManager */
            $this->configurationManager = $this->objectManager->get(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::class);
            $this->configurationManager->setContentObject($GLOBALS['TSFE']->cObj);
            $this->configurationManager->setConfiguration($dispatchController);
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
        ////die(var_dump('<pre>', __METHOD__, $dispatchController));

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
                $requestDispatch = $this->objectManager->get(\TYPO3\CMS\Extbase\Mvc\Web\Request::class);

                $requestDispatch->setControllerVendorName($dispatchController['vendorName']);
                $requestDispatch->setControllerExtensionName($dispatchController['extensionName']);
                $requestDispatch->setPluginName($dispatchController['pluginName']);
                $requestDispatch->setControllerName($dispatchController['controller']);
                $requestDispatch->setControllerActionName($dispatchController['action']);

                $requestDispatch->setArguments(GeneralUtility::_GPmerged(
                    sprintf(
                        'tx_%s_%s',
                        strtolower($requestDispatch->getControllerExtensionName()),
                        strtolower($requestDispatch->getPluginName())
                    )
                ));

                $requestDispatch->setFormat(isset($arguments['format']) ? $arguments['format'] : 'html');

                $requestDispatch->setRequestUri(GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL'));
                $requestDispatch->setBaseUri(GeneralUtility::getIndpEnv('TYPO3_SITE_URL'));
                $requestDispatch->setMethod((isset($_SERVER['REQUEST_METHOD'])) ? $_SERVER['REQUEST_METHOD'] : null);

                /** @var \TYPO3\CMS\Extbase\Mvc\Web\Response $responseObject */
                $responseObject = $this->objectManager->get(\TYPO3\CMS\Extbase\Mvc\Web\Response::class);

                /** @var \TYPO3\CMS\Extbase\Mvc\Dispatcher $dispatcher */
                $dispatcher = $this->objectManager->get(\TYPO3\CMS\Extbase\Mvc\Dispatcher::class);
                $dispatcher->dispatch($requestDispatch, $responseObject);

                $response = $responseObject->getContent();
                $responseObject->shutdown();
            }
            else
            {
                // Dispatch using Bootstrap with Page-Uid
                $bootstrap = $this->objectManager->get(\TYPO3\CMS\Extbase\Core\Bootstrap::class);
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
     * @throws \TYPO3\CMS\Core\Error\Http\PageNotFoundException
     * @throws \TYPO3\CMS\Core\Error\Http\ServiceUnavailableException
     */
    private function initializeTSFE($id = null)
    {
        if (TYPO3_MODE !== 'FE')
        {
            return false;
        }
        $id = intval($id);

        /** @var \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController */
        $GLOBALS['TSFE'] = GeneralUtility::makeInstance(\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController::class, $GLOBALS['TYPO3_CONF_VARS'], $id, 0, true);
        $GLOBALS['TSFE']->sys_page = GeneralUtility::makeInstance(\TYPO3\CMS\Frontend\Page\PageRepository::class);
        $GLOBALS['TSFE']->cObj = $this->objectManager->get(\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::class);

        EidUtility::initLanguage();
        EidUtility::initTCA();

        if(empty($id))
        {
            $id = abs($this->determineRootpageIdByDomain());
        }

        // No Cache for Ajax stuff.
        $GLOBALS['TSFE']->set_no_cache();
        $GLOBALS['TSFE']->connectToDB();
        $GLOBALS['TSFE']->initFEuser();
        $GLOBALS['TSFE']->initUserGroups();

        if(abs($id))
        {
            $GLOBALS['TSFE']->id = $id;
            $GLOBALS['TSFE']->checkAlternativeIdMethods();
            $GLOBALS['TSFE']->determineId();
            $GLOBALS['TSFE']->getPageAndRootline();
            $GLOBALS['TSFE']->initTemplate();
            $GLOBALS['TSFE']->getConfigArray();

            // Initialize RealURL
            if (ExtensionManagementUtility::isLoaded('realurl'))
            {
                $GLOBALS['TSFE']->config['config']['tx_realurl_enable'] = 1;
            }
        }

        $GLOBALS['TSFE']->settingLanguage();
    }

    protected function determineRootpageIdByDomain()
    {
        $domainRecord = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
            'pid, domainName, forced',
            'sys_domain',
            sprintf(
                '`domainName`="%s" AND `redirectTo`="" %s',
                GeneralUtility::getIndpEnv('HTTP_HOST'),
                $GLOBALS['TSFE']->sys_page->enableFields('sys_domain', 0)
            ),
            '',
            '',
            1
        );

        if(count($domainRecord))
        {
            $domainRecord = array_pop($domainRecord);
            return $domainRecord['pid'];
        }

        return null;
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
