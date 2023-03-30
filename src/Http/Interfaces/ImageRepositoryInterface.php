<?php

namespace Devilwacause\UnboundCore\Http\Interfaces;

use Illuminate\Http\Request;

interface ImageRepositoryInterface extends FileInterface
{

    public function show(Request $request, string $fileUUID);

    public function get(string $fileUUID);
}