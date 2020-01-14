<?php
namespace Typoworx\FooBar\Traits;

/**
 * Used for abstract definition of logging pipes
 * f.e. for CliCommand to OutputInterface and/or other logger-facilities
 * by passing Callback-Functions for different log-levels
 *
 * Trait LogDeligator
 */
trait LogDeligator
{
    /**
     * @var \ArrayAccess<Callable>
     */
    protected $loggers;


    /**
     * LogDeligator constructor.
     */
    public function initializeLogDelegator()
    {
        $this->loggers = new \ArrayObject();
    }

    /**
     * @param string $facilityName
     * @param callable $callable
     * @return self
     */
    public function setLogger(string $facilityName, callable $callable) : self
    {
        $this->loggers->offsetSet($facilityName, $callable);

        return $this;
    }

    /**
     * @param string $facilityName
     * @return callable|null
     */
    protected function getLogger(string $facilityName)
    {
        if($this->loggers->offsetExists($facilityName))
        {
            return $this->loggers->offsetGet($facilityName);
        }

        return null;
    }

    /**
     * @param string $facilityName
     * @param string $message
     * @param mixed ...$vars
     * @return bool
     */
    public function log(string $facilityName, string $message, ...$vars)
    {
        $logger = $this->getLogger($facilityName);

        if(!is_callable($logger))
        {
            return false;
        }

        if(!empty($vars))
        {
            $message = vsprintf($message, $vars);
        }

        call_user_func($logger, $message);
    }
}
