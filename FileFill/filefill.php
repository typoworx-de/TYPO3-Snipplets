<?php
$AppContext=$_SERVER['TYPO3_CONTEXT'];

$proxyUri = 'https://www.my-production-domain.de';

$requestProto='http' . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 's' : '');
$requestDomain=$_SERVER['SERVER_NAME'];
$requestUri = $_SERVER['REQUEST_URI'];
$requestFile = $_SERVER['DOCUMENT_ROOT'] . $requestUri;

// Check Application-Context
if(!preg_match('~^(Development|Production/Staging)~', $AppContext))
{
    header('HTTP/1.0 404 Not Found');
    die();
}

if(is_file($requestFile))
{
    header(sprintf(
        'Retry-After: %s',
        gmdate('D, d M Y H:i:s', strtotime('+30 seconds')).' GMT')
    );
}
else
{
    if(!is_dir(dirname($requestFile)))
    {
        mkdir(dirname($requestFile), 2775, true);
    }

    if(touch($requestFile))
    {
        $f= fopen($requestFile, 'rw+');
        if($f && flock($f, LOCK_EX))
        {
            fwrite($f, file_get_contents($proxyUri . $requestUri));
            flock($f, LOCK_UN);

            header(sprintf(
                'location: %s://%s%s',
                $requestProto, $requestDomain, $requestUri
            ));
        }
        else
        {
            header(sprintf(
                'Retry-After: %s',
                gmdate('D, d M Y H:i:s', strtotime('+30 seconds')).' GMT')
            );
        }

        fclose($f);
        die();
    }
}

header('HTTP/1.0 404 Not Found');
die();
