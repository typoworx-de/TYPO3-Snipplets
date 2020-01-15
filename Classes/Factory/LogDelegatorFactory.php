<?php
namespace Typoworx\FooBar\Factory;

use Typoworx\FooBar\Traits\LogDelegatorInterface;
use Typoworx\FooBar\Traits\LogDeligator;
use Typoworx\FooBar\Utility\StaticObjectManager;

/**
 * Class LogDelegatorFactory
 */
class LogDelegatorFactory
{
    use LogDeligator;

    const VERBOSITY_NORMAL = 0;
    const VERBOSITY_DEBUG = 1;

    /**
     * @var int
     */
    protected $verbosity = self::VERBOSITY_NORMAL;

    /**
     * @return self
     */
    public static function create() : self
    {
        /** @var LogDeligator $instance */
        $instance = StaticObjectManager::get(self::class);
        $instance->initializeLogDelegator();

        return $instance;
    }

    /**
     * @param int $verbosity
     */
    public function setVerbosity(int $verbosity)
    {
        $this->verbosity = $verbosity;
    }

    /**
     * @return int
     */
    public function getVerbosity() : int
    {
        return $this->verbosity;
    }
}
