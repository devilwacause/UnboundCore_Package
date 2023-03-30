<?php

namespace Devilwacause\UnboundCore\Http\Interfaces;

interface FileRepositoryInterface extends FileInterface
{
    public function download(string $fileUUID);
}