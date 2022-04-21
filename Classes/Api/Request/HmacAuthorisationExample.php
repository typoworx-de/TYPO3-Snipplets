<?php
namespace Foo\Bar\Api\Request;

use Psr\Http\Message\RequestInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class Authorisation
{
    private string $headerPrefix = 'Bearer';
    private string $username;
    private string $token;
    private ?\DateTime $signatureTime = null;


    public static function create(string $username, string $token) : self
    {
        /** @var self $instance */
        $instance = GeneralUtility::makeInstance(static::class, $username, $token);

        return $instance;
    }

    public function __construct(string $username, string $token)
    {
        $this->username = $username;
        $this->token = $token;
    }

    private function getSignatureTimeStamp() : string
    {
        if($this->signatureTime === null)
        {
            $this->signatureTime = new \DateTime('now', new \DateTimeZone('GMT'));
        }

        return $this->signatureTime->format('D, d M Y H:i:s \G\M\T');
    }

    public function signRequest(RequestInterface $request, $options = []) : RequestInterface
    {
        $this->signatureDate = $this->getSignatureTimeStamp();

        $request->setHeader('date', $this->signatureDate);
        $request->setHeader(
            'authorization',
            $this->getAuthHeader($request, $this->signatureDate, $options)
        );

        return $request;
    }

    private function getAuthHeader(RequestInterface $request) : string
    {
        return sprintf(
            '%s %s:%s',
            $this->headerPrefix,
            $this->username,
            $this->calculateMessageDigest($request)
        );
    }

    private function calculateMessageDigest(RequestInterface $request) : string
    {
        return base64_encode(
            $this->createHmac($this->createStringToSign($request))
        );
    }

    private function createHmac(string $input) :? string
    {
        $hash = hash_hmac(
            'sha1',
            $input,
            base64_decode($this->token),
            true
        );

        return empty($hash)
            ? null
            : $hash
        ;
    }

    private function createStringToSign(RequestInterface $request) : string
    {
        $requestUri = $request->getUri();

        $signatureRequestString = utf8_encode(sprintf(
            "%s\n%s\n%s\n%s%s",
            $request->getMethod(),
            $this->signatureDate,
            $this->username,
            ltrim($requestUri->getPath(), '/'),
            $requestUri->getQuery() ? '?' . $requestUri->getQuery() : '',
        ));

        return $signatureRequestString;
    }
}
