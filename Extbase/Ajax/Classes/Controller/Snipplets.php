
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

class DummyController extends ActionController
{
     public funcion anyAction()
     {
          /**
           *Rewrite Template-Path on demand
           */
          $action = 'InternalError';

          // Method 1
          if ($this->response instanceof \TYPO3\CMS\Extbase\Mvc\Web\Response)
          {
              $this->request->setControllerActionName($action);
              if($this->view->canRender($this->controllerContext))
              {
                  $this->response->setContent($this->view->render());
              }

              $this->response->setStatus(500);
          }

          // Method 2
          $templatePathPath = sprintf(
              'EXT:%s/Resources/Private/Templates/%s/%s.%s',
              $this->request->getControllerExtensionKey(),
              $this->request->getControllerName(),
              ucfirst($action),
              $this->request->getFormat()
          );
          $templateFile = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($templatePathPath);
          $this->view->setTemplatePathAndFilename($templateFile);
      }
     
                
      /**
      * Fetch Exceptions in Controller
      *
      * @return void
      * @override \TYPO3\CMS\Extbase\Mvc\Controller\ActionController
      */
      protected function callActionMethod()
      {
        try
        {
            parent::callActionMethod();
        }
        catch (\Exception $exception)
        {
            $action = 'InternalError';
            //DebuggerUtility::var_dump($this->controllerContext);

            if ($this->response instanceof \TYPO3\CMS\Extbase\Mvc\Web\Response)
            {
                $this->request->setControllerActionName('internalError');
                if($this->view->canRender($this->controllerContext))
                {
                    $this->response->setContent($this->view->render());
                }

                $this->response->setStatus(500);
            }

            var_dump($templateFile);

            if(!empty($exception))
            {
                $this->view->assign('Exception', $exception);
            }
        }
      }
