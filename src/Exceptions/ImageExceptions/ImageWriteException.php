<?php

namespace Devilwacause\UnboundCore\Exceptions\ImageExceptions;

use Illuminate\Http\Response;
use Devilwacause\UnboundCore\Exceptions\UnboundCMSException;
class ImageWriteException extends UnboundCMSException
{
    public function status(): int
    {
        return Response::HTTP_INTERNAL_SERVER_ERROR;
    }

    public function help(): string {
        return "Could not store image to storage.";
    }

    public function error(): string {
        return "Could not store image to storage.";
    }
}