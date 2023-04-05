<?php

namespace Devilwacause\UnboundCore\Http\Interfaces;

use Illuminate\Database\Eloquent\Collection;

interface FolderRepositoryInterface
{
    public function findById($id);

    public function findByNameAndParent($folder_name, $parent);

    public function createFolder($folder_name, $parent);

}