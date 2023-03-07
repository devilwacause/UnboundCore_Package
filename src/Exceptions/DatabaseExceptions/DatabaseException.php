<?php

namespace Devilwacause\UnboundCore\Exceptions\DatabaseExceptions;

use Devilwacause\UnboundCore\Exceptions\UnboundCMSException;
use Illuminate\Http\Response;

class DatabaseException extends UnboundCMSException
{
    private $database_message;

    public function __construct($database_message = '') {
        $this->database_message = $database_message;
    }
    public function status(): int
    {
        return Response::HTTP_INTERNAL_SERVER_ERROR;
    }

    public function help(): string {
        return "Database Error!";
    }

    public function error(): string {
        return $this->database_message;
    }
}