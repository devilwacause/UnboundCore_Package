<?php

namespace Devilwacause\UnboundCore\Exceptions;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Devilwacause\UnboundCore\Exceptions\UnboundCMSError;

abstract class UnboundCMSException extends Exception
{
    abstract public function status(): int;

    abstract public function help(): string;

    abstract public function error(): string;

    public function render(Request $request): Response
    {
        $error = new UnboundCMSError($this->help(), $this->error());
        return response($error->toArray(), $this->status());
    }
}