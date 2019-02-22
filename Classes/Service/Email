<?php
namespace Typoworx\Foobar\Service\Email;

use gnupg;

use Swift_DependencyContainer;
use Swift_Message;
use Swift_Signers_BodySigner;
use Swift_SwiftException;

use TYPO3\CMS\Extbase\Utility\DebuggerUtility;

/**
 * Class SwiftMailGpgSigner
 * @package Typoworx\Typoworx\Service\Email
 *
 * @author Chris Corbyn (inspired by original code)
 *         https://github.com/Mailgarant/switfmailer-openpgp/blob/master/OpenPGPSigner.php
 */
class SwiftMailGpgSigner implements Swift_Signers_BodySigner
{
    /**
     * @var gnupg
     */
    protected $gnupg;

    /**
     * The signing hash algorithm. 'MD5', SHA1, or SHA256. SHA256 (the default) is highly recommended
     * unless you need to deal with an old client that doesn't support it. SHA1 and MD5 are
     * currently considered cryptographically weak.
     *
     * This is apparently not supported by the PHP GnuPG module.
     *
     * @type string
     */
    protected $micalg = 'SHA256';

    /**
     * An associative array of identifier=>keyFingerprint for the recipients we'll encrypt the email
     * to, where identifier is usually the email address, but could be anything used to look up a
     * key (including the fingerprint itself). This is populated either by autoAddRecipients or by
     * calling addRecipient.
     *
     * @type array
     */
    protected $recipientKeys = [];

    /**
     * The fingerprint of the key that will be used to sign the email. Populated either with
     * autoAddSignature or addSignature.
     *
     * @type string
     */
    protected $signingKey;

    /**
     * An associative array of keyFingerprint=>passwords to decrypt secret keys (if needed).
     * Populated by calling addKeyPassphrase. Pointless at the moment because the GnuPG module in
     * PHP doesn't support decrypting keys with passwords. The command line client does, so this
     * method stays for now.
     *
     * @type array
     */
    protected $keyPassphrases = [];

    /**
     * Specifies the home directory for the GnuPG keyrings. By default this is the user's home
     * directory + /.gnupg, however when running on a web server (eg: Apache) the home directory
     * will likely not exist and/or not be writable. Set this by calling setGPGHome before calling
     * any other encryption/signing methods.
     *
     * @var string
     */
    protected $gnupgHome = null;

    /**
     * @var bool
     */
    protected $enableEncryption = true;

    /**
     * @var bool
     */
    protected $enableSignage = true;


    /**
     * @param null|string $signingKey
     * @param array $recipientKeys
     * @param null|string $gnupgHome
     * @throws \Swift_SwiftException
     * @return self
     */
    public function __construct($signingKey = null, array $recipientKeys = [], $gnupgHome = null)
    {
        $this->signingKey = $signingKey;
        $this->recipientKeys = $recipientKeys;

        if(!empty($gnupgHome))
        {
            $this->setGnupgHome($gnupgHome);
        }

        if($signingKey === null)
        {
            $this->enableSignage = false;
        }

        $this->initGNUPG();
    }

    /**
     * @param null|string $signingKey
     * @param array $recipientKeys
     * @param null|string $gnupgHome
     * @return self
     * @throws \Swift_SwiftException
     */
    public static function newInstance($signingKey = null, array $recipientKeys = [], $gnupgHome = null)
    {
        return new self($signingKey, $recipientKeys, $gnupgHome);
    }

    /**
     * @param bool $enableEncryption
     */
    public function setEnableEncryption(bool $enableEncryption)
    {
        $this->enableEncryption = $enableEncryption;
    }

    /**
     * @param bool $enableSignage
     */
    public function setEnableSignage(bool $enableSignage)
    {
        $this->enableSignage = $enableSignage;
    }

    /**
     * @param string|null $gnupgHome
     * @return bool
     */
    public function setGnupgHome($gnupgHome = null) : bool
    {
        if(empty($gnupgHome) || !is_dir($gnupgHome))
        {
            return false;
        }

        $this->gnupgHome = $gnupgHome;
        putenv("GNUPGHOME=" . escapeshellcmd($this->gnupgHome));

        return true;
    }

    /**
     * @param string $identifier
     * @param string $passPhrase
     * @throws \Swift_SwiftException
     */
    public function addSignature(string $identifier, string $passPhrase = '')
    {
        $keyFingerprint = $this->getKey($identifier, 'sign');
        $this->signingKey = $keyFingerprint;

        if (!empty($passPhrase))
        {
            $this->addKeyPassphrase($keyFingerprint, $passPhrase);
        }
    }

    /**
     * @param string $identifier
     * @param string $passPhrase
     * @throws \Swift_SwiftException
     */
    public function addKeyPassphrase(string $identifier, string $passPhrase)
    {
        $keyFingerprint = $this->getKey($identifier, 'sign');
        $this->keyPassphrases[$keyFingerprint] = $passPhrase;
    }

    /**
     * Adds a recipient to encrypt a copy of the email for. If you exclude a key fingerprint, we
     * will try to find a matching key based on the identifier. However if no match is found, or
     * if multiple valid keys are found, this will fail. Specifying a key fingerprint avoids these
     * issues.
     *
     * @param string $identifier
     * an email address, but could be a key fingerprint, key ID, name, etc.
     *
     * @param string $keyFingerprint
     * @throws \Swift_SwiftException
     */
    public function addRecipient($identifier, $keyFingerprint = null)
    {
        if (!$keyFingerprint)
        {
            $keyFingerprint = $this->getKey($identifier, 'encrypt');
        }

        $this->recipientKeys[$identifier] = $keyFingerprint;
    }

    /**
     * @param \Swift_Message $message
     * @return $this|\Swift_Signers_BodySigner
     * @throws \Swift_DependencyException
     * @throws \Swift_SwiftException
     */
    public function signMessage(Swift_Message $message)
    {
        $originalMessage = $this->createMessage($message);

        $message->setChildren([]);
        $message->setEncoder(Swift_DependencyContainer::getInstance()->lookup('mime.rawcontentencoder'));

        $type = $message->getHeaders()->get('Content-Type');
        $type->setValue('multipart/signed');
        $type->setParameters([
            'micalg'   => sprintf("pgp-%s", strtolower($this->micalg)),
            'protocol' => 'application/pgp-signature',
            'boundary' => $message->getBoundary()
        ]);

        if (!$this->signingKey)
        {
            foreach ($message->getFrom() as $key => $value)
            {
                $this->addSignature($this->getKey($key, 'sign'));
            }
        }

        if (!$this->signingKey)
        {
            throw new Swift_SwiftException('Signing has been enabled, but no signature has been added. Use autoAddSignature() or addSignature()');
        }

        $plainBody = $originalMessage->toString();

        // Remove excess trailing newlines (RFC3156 section 5.4)
        $plainBody = preg_replace_callback(
            '/(\r\n|\r|\n)/',
            function($line)
            {
                return rtrim($line[0]) . "\r\n";
            },
            rtrim($plainBody)
        );

        if ($this->enableSignage === true && $this->signingKey !== null)
        {
            // Swiftmailer is automatically changing content type and this is the hack to prevent it
            $signedBody = sprintf(
                "This is an OpenPGP/MIME signed message (RFC 4880 and 3156)\n\n" .
                "--%s\n" .
                "%s\n" .
                "--%s\n" .
                "Content-Type: application/pgp-signature; name=\"signature.asc\"\n" .
                "Content-Description: OpenPGP digital signature\n" .
                "Content-Disposition: attachment; filename=\"signature.asc\"\n\n" .
                "%s\n" .
                "--%s--\n",
                $message->getBoundary(),
                $plainBody,
                $message->getBoundary(),
                $this->pgpSignString($plainBody, $this->signingKey),
                $message->getBoundary()
            );

            $message->setBody($signedBody);

            $messageBody = sprintf(
                "%s\r\n%s",
                $message->getHeaders()->get('Content-Type')->toString(),
                $signedBody
            );
        }
        else
        {
            $messageBody = $plainBody;
        }

        if ($this->enableEncryption)
        {
            if (!$this->recipientKeys)
            {
                foreach ($message->getTo() as $key => $value)
                {
                    if (!isset($this->recipientKeys[$key]))
                    {
                        $this->addRecipient($key);
                    }
                }
            }

            if (!$this->recipientKeys)
            {
                throw new Swift_SwiftException('Encryption has been enabled, but no recipients have been added. Use autoAddRecipients() or addRecipient()');
            }

            //Create body from signed message
            $encryptedBody = $this->pgpEncryptString($messageBody, array_keys($this->recipientKeys));

            $type = $message->getHeaders()->get('Content-Type');
            $type->setValue('multipart/encrypted');
            $type->setParameters([
                'protocol' => 'application/pgp-encrypted',
                'boundary' => $message->getBoundary()
            ]);

            //Swiftmailer is automatically changing content type and this is the hack to prevent it
            $messageBody = sprintf(
                "This is an OpenPGP/MIME signed message (RFC 4880 and 3156)\n\n" .
                "--%s\n" .
                "Content-Type: application/pgp-encrypted\n" .
                "Content-Description: PGP/MIME version identification\n\n" .
                "Version: 1\n\n" .
                "--%s\n" .
                "Content-Type: application/octet-stream; name=\"encrypted.asc\"\n" .
                "Content-Description: OpenPGP encrypted message\n" .
                "Content-Disposition: inline; filename=\"encrypted.asc\"\n\n" .
                "%s\n" .
                "--%s--\n",
                $message->getBoundary(),
                $message->getBoundary(),
                $encryptedBody,
                $message->getBoundary()
            );

            $message->setBody($messageBody);
        }

        $messageHeaders = $message->getHeaders();
        $messageHeaders->removeAll('Content-Transfer-Encoding');

        return $this;
    }

    /**
     * @return array
     */
    public function getAlteredHeaders()
    {
        return ['Content-Type', 'Content-Transfer-Encoding', 'Content-Disposition', 'Content-Description'];
    }

    /**
     * @return $this
     */
    public function reset()
    {
        $this->gnupg->clearsignkeys();
        $this->gnupg->clearencryptkeys();
        $this->gnupg->cleardecryptkeys();

        return $this;
    }

    /**
     * Encapsulate original Message to be wrapped in PGP-MIME-Part
     * @param \Swift_Message $message
     * @return \Swift_Message
     */
    protected function createMessage(Swift_Message $message)
    {
        $mimeEntity = new Swift_Message('', $message->getBody(), $message->getContentType(), $message->getCharset());
        $mimeEntity->setChildren($message->getChildren());

        $messageHeaders = $mimeEntity->getHeaders();
        $messageHeaders->remove('Message-ID');
        $messageHeaders->remove('Date');
        $messageHeaders->remove('Subject');
        $messageHeaders->remove('MIME-Version');
        $messageHeaders->remove('To');
        $messageHeaders->remove('From');

        return $mimeEntity;
    }

    /**
     * @throws Swift_SwiftException
     */
    protected function initGNUPG()
    {
        if (!class_exists('gnupg'))
        {
            throw new Swift_SwiftException('PHPMailerPGP requires the GnuPG class');
        }

        /*
        if (!$this->gnupgHome)
        {
            if (isset($_SERVER['HOME']))
            {
                $this->gnupgHome = $_SERVER['HOME'] . '/.gnupg';
            }
            else if (getenv('HOME'))
            {
                $this->gnupgHome = getenv('HOME') . '/.gnupg';
            }
        }

        if (!$this->gnupgHome)
        {
            throw new Swift_SwiftException(sprintf(
                'Unable to detect GnuPG home path, please call %s::setGPGHome()',
                get_class($this)
            ));
        }
        */

        putenv("GNUPGHOME=" . escapeshellcmd($this->gnupgHome));

        if (!is_dir($this->gnupgHome))
        {
            throw new Swift_SwiftException('GnuPG home path does not exist');
        }

        if (!$this->gnupg)
        {
            $this->gnupg = new gnupg();
        }

        $this->gnupg->seterrormode(GNUPG_ERROR_EXCEPTION);
    }

    /**
     * @param string $plaintext
     * @param string $keyFingerprint
     * @return string
     * @throws \Swift_SwiftException
     */
    protected function pgpSignString(string $plaintext, string $keyFingerprint)
    {
        $passPhrase = null;
        if (isset($this->keyPassphrases[$keyFingerprint]) && !$this->keyPassphrases[$keyFingerprint])
        {
            $passPhrase = $this->keyPassphrases[$keyFingerprint];
        }

        $this->gnupg->clearsignkeys();
        $this->gnupg->addsignkey($keyFingerprint, $passPhrase);
        $this->gnupg->setsignmode(gnupg::SIG_MODE_DETACH);
        $this->gnupg->setarmor(1);

        $signed = $this->gnupg->sign($plaintext);

        if ($signed)
        {
            return $signed;
        }

        throw new Swift_SwiftException('Unable to sign message (perhaps the secret key is encrypted with a passphrase?)');
    }

    /**
     * @param string $plaintext
     * @param array $keyFingerprints
     * @return string
     * @throws \Swift_SwiftException
     */
    protected function pgpEncryptString(string $plaintext, array $keyFingerprints)
    {
        $this->gnupg->clearencryptkeys();

        foreach ($keyFingerprints as $keyFingerprint)
        {
            $this->gnupg->addencryptkey($keyFingerprint);
        }

        $this->gnupg->setarmor(1);

        $encrypted = $this->gnupg->encrypt($plaintext);

        if ($encrypted)
        {
            return $encrypted;
        }

        throw new Swift_SwiftException('Unable to encrypt message');
    }

    /**
     * @param string $identifier
     * @param string $purpose
     * @return mixed
     * @throws \Swift_SwiftException
     */
    protected function getKey(string $identifier, string $purpose)
    {
        $fingerprints = [];

        $keys = $this->gnupg->keyinfo($identifier);
        if(!count($keys))
        {
            foreach (glob(sprintf('%s/*%s*.asc', $this->gnupgHome, $identifier)) as $file)
            {
                $this->gnupg->import(file_get_contents($file));
            }

            $keys = $this->gnupg->keyinfo($identifier);
        }

        foreach ($keys as $key)
        {
            if ($this->isValidKey($key, $purpose))
            {
                foreach ($key['subkeys'] as $subKey)
                {
                    if ($this->isValidKey($subKey, $purpose))
                    {
                        $fingerprints[] = $subKey['fingerprint'];
                    }
                }
            }
        }

        if (count($fingerprints) === 1)
        {
            return $fingerprints[0];
        }

        if (count($fingerprints) > 1)
        {
            throw new Swift_SwiftException(sprintf('Found more than one active key for %s use addRecipient() or addSignature()', $identifier));
        }

        throw new Swift_SwiftException(sprintf('Unable to find an active key to %s for %s,try importing keys first', $purpose, $identifier));
    }

    /**
     * @param array $key
     * @param string $purpose
     * @return bool
     */
    protected function isValidKey(array $key, string $purpose) : bool
    {
        return !(
            $key['disabled'] || $key['expired'] || $key['revoked'] ||
            ($purpose == 'sign' && !$key['can_sign']) ||
            ($purpose == 'encrypt' && !$key['can_encrypt'])
        );
    }
}
