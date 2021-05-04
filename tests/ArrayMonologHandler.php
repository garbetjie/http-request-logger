<?php

namespace Garbetjie\RequestLogging\Http\Tests;

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Handler\HandlerInterface;
use function func_num_args;

class ArrayMonologHandler extends AbstractProcessingHandler implements HandlerInterface
{
    private $logs = [];

    protected function write(array $record): void
    {
        $this->logs[] = $record;
    }

    public function clear()
    {
        $this->logs = [];
    }

    public function logs($index = null)
    {
        if (func_num_args() > 0) {
            return $this->logs[$index];
        } else {
            return $this->logs;
        }
    }
}
