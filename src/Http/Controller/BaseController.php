<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Http\ErrorHandler;
use App\Http\Request;
use App\Http\Response;

abstract class BaseController
{
    protected $errorHandler;

    public function __construct(ErrorHandler $errorHandler)
    {
        $this->errorHandler = $errorHandler;
    }

    protected function tryRun(callable $fn): Response
    {
        try {
            return $fn();
        } catch (\Throwable $e) {
            return $this->errorHandler->handle($e);
        }
    }
}
