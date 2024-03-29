<?php

namespace Devilwacause\UnboundCore\Http\Interfaces;

use Illuminate\Http\Request;

interface FileInterface
{
    public function findByUUID(string $UUID);

    //public function validateCreate(Request $request);

    //public function create(Request $request);

    public function update(Request $request);

    public function change(Request $request);

    public function move(Request $request);

    public function copy(Request $request);

    public function remove(Request $request);
}