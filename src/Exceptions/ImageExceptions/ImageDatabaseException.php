<?php

namespace Devilwacause\UnboundCore\Exceptions\ImageExceptions;

use Illuminate\Http\Response;
use Devilwacause\UnboundCore\Exceptions\UnboundCMSException;
class ImageDatabaseException extends UnboundCMSException
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
        return "Could not store image to database.";
    }

    public function error(): string {
        if($this->database_message === null) {
            return "Could not store image to database.";
        }else{
            return $this->database_message;
        }
    }
}