<?php

namespace Devilwacause\UnboundCore\Exceptions\ImageExceptions;

use Illuminate\Http\Response;
use Devilwacause\UnboundCore\Exceptions\UnboundCMSException;

class FileDeleteException extends UnboundCMSException
{
    public function status(): int
    {
        return Response::HTTP_INTERNAL_SERVER_ERROR;
    }

    public function help(): string {
        return "Could not delete file from storage.";
    }

    public function error(): string {
        return "Could not delete file from storage.";
    }
}