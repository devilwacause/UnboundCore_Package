<?php

namespace Devilwacause\UnboundCore\Http\Repositories;

use Devilwacause\UnboundCore\Exceptions\DatabaseExceptions\DatabaseException;
use Devilwacause\UnboundCore\Models\Folder;
use Devilwacause\UnboundCore\Http\Interfaces\FolderRepositoryInterface;

class FolderRepository implements FolderRepositoryInterface
{
    public function findById($id) {
        return Folder::where('id', $id)->first();
    }

    public function findByNameAndParent($folder_name, $parent) {
        if($parent === null) {
            return Folder::where('folder_name', $folder_name)->whereNull('parent_id')->first();
        }else{
            return Folder::where('folder_name', $folder_name)->where('parent_id', $parent)->first();
        }
    }

    public function createFolder($folder_name, $parent) {
        if($folder_name === null) {
            return null;
        }else{
            try {
                $folder = Folder::create([
                    'parent_id' => $parent,
                    'folder_name' => $folder_name
                ]);
            }catch(\Exception $e) {
                throw new DatabaseException($e->getMessage());
            }
            return $folder;
        }
    }


}