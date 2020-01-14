<?php
namespace Typoworx\FooBar\Traits\Controller;

use TYPO3\CMS\Extbase\Mvc\Web\Response;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;

/**
 * Trait AjaxResponseTrait
 *
 * Adds optional AJAX-Response using Web-Response Object
 * removing Fluid-View to any controller.
 * Requests should be checked using ::isAjaxRequest
 * to switch desired output (either pure AJAX or classic Fluid-Response)
 */
trait AjaxResponseTrait
{
    /**
     * @var \TYPO3\CMS\Extbase\Mvc\Web\Response
     */
    protected $response;

    /**
     * @param \TYPO3\CMS\Extbase\Mvc\View\ViewInterface $view
     * @return \TYPO3\CMS\Extbase\Mvc\View\ViewInterface|void
     * @throws \Exception
     */
    protected function initializeView(ViewInterface $view)
    {
        if($this->isAjaxRequest())
        {
            if($this->request->getFormat() !== 'html')
            {
                $this->createAjaxResponse();
            }
        }
        else
        {
            parent::initializeView($view);
        }
    }

    /**
     * @param \TYPO3\CMS\Extbase\Mvc\RequestInterface $request
     * @param \TYPO3\CMS\Extbase\Mvc\ResponseInterface $response
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException
     */
    public function processRequest(\TYPO3\CMS\Extbase\Mvc\RequestInterface $request, \TYPO3\CMS\Extbase\Mvc\ResponseInterface $response)
    {
        parent::processRequest($request, $response);

        if($this->isAjaxRequest())
        {
            $this->response->send();
            $this->response->shutdown();
        }
    }

    /**
    * @return ViewInterface
    * @api
    */
    protected function resolveView()
    {
        if(!$this->isAjaxRequest())
        {
            return parent::resolveView();
        }
    }

    protected function callActionMethod()
    {
        parent::callActionMethod();

        if($this->isAjaxRequest())
        {
            switch($this->request->getFormat())
            {
                case 'json':
                    $this->response->setHeader('Content-Type', 'application/json', false);
                    break;
            }
        }
    }

    protected function createAjaxResponse()
    {
        $this->view = null;

        // Replace Response-Class with full-featured Web\Response
        $this->request->setArgument('forceAjax', true);
        $this->response = $this->objectManager->get(Response::class);
    }

    protected function serviceDownAction()
    {
        $this->response->setHeader('Status', '503 Service Unavailable');
        $this->errorAction('Service temporarely not available');
    }

    protected function errorAction(string $message = '')
    {
        if(empty($message))
        {
            $message = parent::errorAction();
        }
        else
        {
            $this->clearCacheOnError();
            $this->addErrorFlashMessage();
            $this->forwardToReferringRequest();
        }

        if(!$this->isAjaxRequest())
        {
            switch($this->request->getFormat())
            {
                case 'json':
                    $this->response->setContent(json_encode(
                        [
                            'error' => true,
                            'message' => $message,
                        ]
                    ));
                    break;

                case 'html':
                    $this->response->setContent(
                        sprintf('<html><body><h1>Error</h1><p>%s</p></body></html>', $message)
                    );
                    break;

                default:
                    $this->response->setContent(sprintf('Error: %s', $message));
                    break;
            }
        }

        // Check if custom http-status (f.e. 503 service not available) has been set previously
        if(!$this->response->getStatus() || $this->response->getStatus() === 200)
        {
            $this->response->setStatus(500, 'Internal Error');
        }

        $this->response->send();
        $this->response->shutdown();
    }

    protected function isAjaxRequest()
    {
        if($this->request->hasArgument('forceAjax') && $this->request->getArgument('forceAjax') == true)
        {
            return true;
        }

        return (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest' );
    }

    /**
     * @param \Exception|\Throwable|null $e
     * @return false|string
     * @throws \Throwable
     */
    protected function throwException($e = null)
    {
        $errorTrace = null;
        if($e instanceof \Throwable || $e instanceof \Exception)
        {
            $errorTrace = sprintf('%s, Line %d', __METHOD__, $e->getLine());
        }


        $this->response->setStatus(500, 'Internal Error');

        switch($this->request->getFormat())
        {
            case 'json':
                $this->sendJsonResponse(json_encode([
                    'Exception' => [
                        'Message' => 'Internal Error',
                        'ErrorTrace' => $errorTrace,
                    ]
                ]));
                break;

            default:
            case 'html':
                throw $e;
                break;
        }
    }

    /**
     * @param string $response
     */
    protected function sendJsonResponse(string $response)
    {
        $this->response->setContent($response);
        $this->response->getHeaders();
        $this->response->shutdown();
    }
}
