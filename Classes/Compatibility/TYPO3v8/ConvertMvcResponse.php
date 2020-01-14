<?php
namespace Typoworx\FooBar\Compatibility\TYPO3v8;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Extbase\Mvc\Response as MvcResponse;
use TYPO3\CMS\Extbase\Mvc\ResponseInterface as MvcResponseInterface;

/**
 * Trait ConvertMvcResponse
 * @description
 * Converts a TYPO3 MvcResponse to Symfony Psr\Http\Message\ResponseInterface
 * f.e. for eID Requests build as semi-compatible TYPO3-v9 Dispatcher.
 */
trait ConvertMvcResponse
{
    /**
     * @param \TYPO3\CMS\Extbase\Mvc\ResponseInterface $mvcResponse
     * @param \Psr\Http\Message\ResponseInterface $response
     */
    protected function convertToHttpResponse(MvcResponseInterface &$mvcResponse, ResponseInterface &$response)
    {
        /**
         * Convert TYPO3\CMS\Extbase\Mvc\Response
         * into \TYPO3\CMS\Core\Http\Response
         */
        if($mvcResponse instanceof MvcResponse)
        {
            // Convert Status-Code
            $httpStatus = explode(' ', $mvcResponse->getStatus(), 2);
            $statusCode = !empty($httpStatus[0]) && abs($httpStatus[0]) > 0 ? $httpStatus[0] : 200;
            $response->withStatus($statusCode);

            // Convert Headers
            array_walk($mvcResponse->getHeaders(), function($header) use(&$response) {
                list($name, $value) = explode(':', $header, 2);

                $response = $response->withHeader($name, $value);
            });

            // Fetch Content
            $response->getBody()->write((string)$mvcResponse->getContent());

            // Flush MVC-Response
            $mvcResponse->setContent('');
        }
    }
}
