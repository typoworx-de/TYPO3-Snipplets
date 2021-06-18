<?php
/**
Add to .htaccess or vHost

# ----------------------------------------------------------------------
# Stage-FileFiller
# ----------------------------------------------------------------------
<IfModule mod_rewrite.c>
    RewriteCond %{REQUEST_URI} fileadmin/
    RewriteCond %{DOCUMENT_ROOT}%{REQUEST_URI} !-f
    RewriteCond %{DOCUMENT_ROOT}%{REQUEST_URI} !-d
    RewriteRule .* filefill.php [L]
    #RewriteRule ^(fileadmin/.*) filefill.php [L]
</IfModule>
*/

$TYPO3_CONTEXT=$_SERVER['TYPO3_CONTEXT'];

$proxyUri = 'https://www.my-production-page.de';

$requestProto='http' . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 's' : '');
$requestDomain=$_SERVER['SERVER_NAME'];
$requestUri = $_SERVER['REQUEST_URI'];
$requestFile = $_SERVER['DOCUMENT_ROOT'] . $requestUri;

if(!preg_match('~^(Development|Production/Staging)~', $TYPO3_CONTEXT))
{
    header('HTTP/1.0 404 Not Found');
    die();
}

if(!is_file($requestFile))
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
