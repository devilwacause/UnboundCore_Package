<?php

namespace Devilwacause\UnboundCore\Exceptions\FileExceptions;

use Illuminate\Http\Response;
use Devilwacause\UnboundCore\Exceptions\UnboundCMSException;
class FileNotFoundException extends UnboundCMSException
{
    public function status(): int
    {
        return Response::HTTP_NOT_FOUND;
    }

    public function help(): string {
        return "Could not find file.  Are you sure it exists?";
    }

    public function error(): string {
        return "Could not find file.";
    }
}