<?php

namespace Garbetjie\Http\RequestLogging;

use function strtolower;

final class LoggedRequest
{
    /**
     * @var string
     */
    protected $id;

    /**
     * @var float
     */
    protected $started;

    /**
     * @var string
     */
    protected $direction;

    /**
     * @param string $id
     * @param string $direction
     * @param float $started
     */
    public function __construct(string $id, string $direction, float $started)
    {
        $this->id = $id;
        $this->direction = strtolower($direction);
        $this->started = $started;
    }

    /**
     * @return float
     */
    public function started(): float
    {
        return $this->started;
    }

    /**
     * @return string
     */
    public function direction(): string
    {
        return $this->direction;
    }

    /**
     * @return string
     */
    public function id(): string
    {
        return $this->id;
    }
}
