<?php

namespace Devilwacause\UnboundCore\Exceptions\ImageExceptions;

use Illuminate\Http\Response;
use Devilwacause\UnboundCore\Exceptions\UnboundCMSException;
class ImageDeleteException extends UnboundCMSException
{
    public function status(): int
    {
        return Response::HTTP_INTERNAL_SERVER_ERROR;
    }

    public function help(): string {
        return "Could not delete image from storage.";
    }

    public function error(): string {
        return "Could not delete image from storage.";
    }
}