<?php

namespace Devilwacause\UnboundCore\Exceptions\ImageExceptions;

use Illuminate\Http\Response;
use Devilwacause\UnboundCore\Exceptions\UnboundCMSException;
class ImageNotFoundException extends UnboundCMSException
{
    public function status(): int
    {
        return Response::HTTP_NOT_FOUND;
    }

    public function help(): string {
        return "Could not find image.  Are you sure it exists?";
    }

    public function error(): string {
        return "Could not find image.";
    }
}