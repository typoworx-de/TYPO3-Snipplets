<?php
namespace Typoworx\FooBar\Domain\Type;

use DateTime;
use DateTimeZone;

/**
 * Class LocalDateTime
 */
class LocalDateTime extends DateTime
{
    const DATE_TIME_UTC = 'Y-m-d H:i:s';
    const DATE_TIME_GMT = 'D, d M Y H:i:s \G\M\T';

    const FORMAT_MYSQL_DATE_TIME  = 'Y-m-d H:i:s';
    const FORMAT_HTTP_Modified = 'D, d M Y H:i:s e';

    protected static $defaultTimeZone = 'Europe/Berlin';

    /**
     * @var string|null
     */
    protected $format;


    /**
     * LocalDateTime constructor.
     * @param string $time
     * @param \DateTimeZone|null $timezone
     * @param string|null $format
     * @throws \Exception
     */
    public function __construct($time = 'now', DateTimeZone $timezone = null, $format = null)
    {
        if($timezone === null)
        {
            $timezoneIdentifier = empty($GLOBALS['TYPO3_CONF_VARS']['SYS']['phpTimeZone'])
                ? self::$defaultTimeZone
                : $GLOBALS['TYPO3_CONF_VARS']['SYS']['phpTimeZone']
            ;

            $timezone = new DateTimeZone($timezoneIdentifier);
        }

        if($format !== null)
        {
            $this->format = $format;
        }

        parent::__construct($time, $timezone);
    }

    /**
     * @param string $time
     * @param \DateTimeZone|null $timezone
     * @param string|null $format
     * @return self
     */
    public static function create($time = 'now', DateTimeZone $timezone = null, $format = null)
    {
        $class = self::class;
        return new $class($time, $timezone, $format);
    }

    /**
     * @param int $timestamp
     * @param \DateTimeZone|null $timezone
     * @param null $format
     * @return \Typoworx\FooBar\Domain\Type\LocalDateTime|null
     */
    public static function createFromTimestamp(int $timestamp , DateTimeZone $timezone = null, $format = null) :? self
    {
        $class = self::class;

        /** @var self $dateTime */
        $dateTime = new $class(null, $timezone, $format);
        $dateTime->setTimestamp($timestamp);

        return $dateTime;
    }

    /**
     * @param string $format
     * @return string|void
     */
    public function format($format = '') :? string
    {
        if(empty($format))
        {
            if (!empty($this->format))
            {
                $format = $this->format;
            }
        }

        return parent::format($format);
    }
}
