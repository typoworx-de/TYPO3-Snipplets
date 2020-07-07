<?php
namespace Foo\FooBar\Routing\Aspect;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Routing\Aspect\StaticMappableAspectInterface;

/**
 * Class RegexAspectMapper
 * @package Foo\Foo\Routing\Aspect
 */
class RegexAspectMapper implements StaticMappableAspectInterface
{
    /**
     * @var string
     */
    protected $regexPattern;


    /**
     * @param array $settings
     * @throws \InvalidArgumentException
     */
    public function __construct(array $settings)
    {
        $this->regexPattern = !isset($settings['regex']) ?: $settings['regex'];
    }

    /**
     * @param string $value
     * @return string|null
     * @throws \Throwable only in Development-Mode
     */
    public function generate(string $value): ?string
    {
        return $this->isPatternValid($value) ? $value : null;
    }

    /**
     * @param string $value
     * @return string|null
     * @throws \Throwable only in Development-Mode
     */
    public function resolve(string $value): ?string
    {
        return $this->isPatternValid($value) ? $value : null;
    }

    /**
     * @param string $value
     * @return bool
     * @throws \Throwable only in Development-Mode
     */
    protected function isPatternValid(string $value) : bool
    {
        $return = false;

        try
        {
            $pattern = trim($this->regexPattern);

            if(empty($pattern) || $pattern === null)
            {
                $return = false;
            }
            else if(substr($pattern, 0) !== substr($pattern, -1))
            {
                if(in_array(substr($pattern, 0), ['!', '^', '(', '$']))
                {
                    $pattern = '~' . $pattern . '~';
                }
            }

            if(empty($pattern))
            {
                $return = preg_match($pattern, $value);
            }
        }
        catch (\Throwable $e)
        {
            if(!GeneralUtility::getApplicationContext()->isProduction())
            {
                throw $e;
            }
        }

        return $return;
    }
}
