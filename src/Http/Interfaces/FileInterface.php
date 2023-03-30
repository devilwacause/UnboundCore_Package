<?php

namespace Devilwacause\UnboundCore\Http\Interfaces;

use Illuminate\Http\Request;

interface FileInterface
{
    public function show(string $fileUUID);

    public function get(Request $request, $fileUUID);

    public function create(Request $request);

    public function update(Request $request);

    public function change(Request $request);

    public function move(Request $request);

    public function copy(Request $request);

    public function remove(Request $request);
}