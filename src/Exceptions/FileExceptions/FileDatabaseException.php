<?php

namespace Devilwacause\UnboundCore\Exceptions\FileExceptions;

use Illuminate\Http\Response;
use Devilwacause\UnboundCore\Exceptions\UnboundCMSException;
class FileDatabaseException extends UnboundCMSException
{
    public function status(): int
    {
        return Response::HTTP_INTERNAL_SERVER_ERROR;
    }

    public function help(): string {
        return "Could not store file to database.";
    }

    public function error(): string {
        return "Could not store file to database.";
    }
}