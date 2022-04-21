<?php
namespace Foo\Bar\Api\Request;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\MessageTrait;
use GuzzleHttp\Psr7\Uri;

use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;

class ApiRequestFactory implements RequestInterface, RequestFactoryInterface
{
    use MessageTrait;

    /** @var string */
    private $endpointUri;

    private ?Authorisation $authorisation = null;

    private string $method = 'GET';

    /** @var UriInterface */
    private UriInterface $uri;

    private bool $testMode = false;


    /**
     * @param string|UriInterface $endpointUri
     * @param string $method
     */
    public function __construct($endpointUri, string $method = 'GET')
    {
        $endpointUri = rtrim($endpointUri, '/') . '/';

        $this->endpointUri = new Uri($endpointUri);
        $this->uri = new Uri($this->endpointUri);

        $this->setHeader('accept', 'application/xml');
        $this->setHeader('content-type', 'application/xml');
    }

    /**
     * @param string|UriInterface $endpointUri
     * @param string $method
     * @return static
     */
    public static function create($endpointUri, string $method = 'get') : self
    {
        $class = static::class;
        return new $class($endpointUri, $method);
    }

    public function setTestMode(bool $flag)
    {
        $this->testMode = $flag;
    }

    public function reset()
    {
        $this->uri = new Uri($this->endpointUri);
    }

    public function createRequest(string $method, $uri, array $queryParams = []) : self
    {
        $this->reset();

        $uriParts = [];
        if($uri instanceof UriInterface)
        {
            $uriParts['path'] = $uri->getPath();
            parse_str($uri->getQuery(), $uriParts['query']);
        }
        else if(is_string($uri))
        {
            $uriParts = parse_url($uri);

            // Only use if we need to merge $queryParams with those from Uri-String
            if(count($queryParams))
            {
                if(empty($uriParts['query']))
                {
                    $uriParts['query'] = $queryParams;
                }
                else
                {
                    $uriParts['query'] = array_merge(
                        $this->parseQueryString($uriParts['query']),
                        $queryParams
                    );
                }
            }
        }
        else
        {
            throw new \InvalidArgumentException(
                'Invalid request uri provided; must be either string or ' . UriInterface::class
            );
        }

        if(count($uriParts))
        {
            if(is_array($uriParts['query']))
            {
                $uriParts['query'] = http_build_query($uriParts['query']);
            }

            $this->applyParts($uriParts);
        }

        return $this;
    }

    private function parseQueryString(string $query) : array
    {
        parse_str($query, $queryStringArray);

        return $queryStringArray ?? [];
    }

    public function getRequestTarget() :? string
    {
        return $this->endpointUri;
    }

    public function withRequestTarget($requestTarget)
    {
        if (preg_match('#\s#', $requestTarget))
        {
            throw new \InvalidArgumentException(
                'Invalid request target provided; cannot contain whitespace'
            );
        }

        $new = clone $this;
        $new->requestTarget = $requestTarget;

        return $new;
    }

    public function getMethod()
    {
        return strtoupper($this->method);
    }

    public function withMethod($method)
    {
        if (!is_string($method) || $method === '')
        {
            throw new \InvalidArgumentException('Method must be a non-empty string.');
        }

        $this->method = $method;

        return $this;
    }

    public function getUri() :? UriInterface
    {
        return $this->uri;
    }

    public function withUri(UriInterface $uri, $preserveHost = false)
    {
        if ($uri === $this->uri)
        {
            return $this;
        }

        $new = clone $this;
        $new->uri = $uri;

        if (!$preserveHost || !isset($this->headerNames['host']))
        {
            $new->updateHostFromUri();
        }

        return $new;
    }

    public function withQuery($query) : self
    {
        if(is_array($query))
        {
            $query = http_build_query($query);
        }

        $this->uri = $this->uri->withQuery($query);

        return $this;
    }

    public function withPath(string $path = null) : self
    {
        $this->uri = $this->uri->withPath($path ?? '');

        return $this;
    }

    public function withAuthorisation(string $username = null, string $token = null) : self
    {
        $this->authorisation = Authorisation::create($username, $token);

        return $this;
    }

    public function setHeader($header, $value)
    {
        $this->assertHeader($header);
        $value = $this->normalizeHeaderValue($value);
        $normalized = strtolower($header);

        if (isset($this->headerNames[ $normalized ]))
        {
            unset($this->headers[ $this->headerNames[ $normalized ] ]);
        }

        $this->headerNames[ $normalized ] = $header;
        $this->headers[$header] = $value;
    }

    public function apply(...$uriParts) : self
    {
        $this->applyParts($uriParts);

        return $this;
    }

    public function applyParts(array $uriParts) : self
    {
        foreach($uriParts as $key => $uriPart)
        {
            if(empty($uriPart)) continue;

            $callee = sprintf('with%s', ucfirst($key));
            $getter = sprintf('get%s', ucfirst($key));

            if($this->endpointUri->$getter() !== '')
            {
                $uriPart = $this->endpointUri->$getter() . $uriPart;
            }

            if(method_exists($this->uri, $callee))
            {
                $this->uri = $this->uri->$callee($uriPart);
            }
            else
            {
                throw new \InvalidArgumentException(
                    'Invalid property provided to ' . UriInterface::class
                );
            }
        }

        return $this;
    }

    public function getClient() : ClientInterface
    {
        if($this->client === null)
        {
            $this->client = new Client([ 'base_uri' => $this->endpointUri ]);
        }

        return $this->client;
    }

    private function signRequest() : RequestInterface
    {
        if($this->authorisation instanceof Authorisation)
        {
            return $this->authorisation->signRequest($this);
        }

        return $this;
    }

    public function send(array $options = []) : ResponseInterface
    {
        try
        {
            if($this->testMode)
            {
                $this->setHeader('testing', 'true');
            }

            $signedRequest = $this->signRequest();

            return $this->getClient()->send($signedRequest, $options);
        }
        catch (\GuzzleHttp\Exception\ClientException $e)
        {
            $response = $e->getResponse();

            // Doh something went wrong
            die(var_dump(
                $e->getMessage(),
                $response->getStatusCode(3),
                $response->getBody()->getContents()
            ));
        }
    }

    /*
    public function sendAsync(array $options = [])
    {
        $signedRequest = $this->signRequest();

        return $this->getClient()->sendAsync($signedRequest, $options);
    }
    */

    private function updateHostFromUri() : self
    {
        $host = $this->uri->getHost();

        if (empty($host))
        {
            return $this;
        }

        if (($port = $this->uri->getPort()) !== null)
        {
            $host .= ':' . $port;
        }

        if (isset($this->headerNames['host']))
        {
            $header = $this->headerNames['host'];
        }
        else
        {
            $header = 'Host';
            $this->headerNames['host'] = 'Host';
        }

        // Ensure Host is the first header.
        // See: http://tools.ietf.org/html/rfc7230#section-5.4
        $this->headers = [$header => [$host]] + $this->headers;

        return $this;
    }
}
