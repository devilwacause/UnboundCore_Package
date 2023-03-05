<?php

namespace Devilwacause\UnboundCore\Exceptions;

use JsonSerializable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Contracts\Support\Arrayable;

class UnboundCMSError
{
    public function __construct(private string $help = '', private string $error = '')
    {

    }

    public function toArray(): array
    {
        return [
            'error' => $this->error,
            'help' => $this->help,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function toJson($options = 0.0)
    {
        $jsonEncoded = json_encode($this->jsonSerialize(), $options);
        return $jsonEncoded;
    }
}