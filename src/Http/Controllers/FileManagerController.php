<?php

namespace Devilwacause\UnboundCore\Http\Controllers;

use Devilwacause\UnboundCore\Exceptions\FolderExceptions\FolderNotFoundException;
use Devilwacause\UnboundCore\Models\Folder;
use Devilwacause\UnboundCore\Models\File;
use Devilwacause\UnboundCore\Models\Image;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class FileManagerController extends BaseController
{
    public function showdir($folder_id = null) {
        $folder = Folder::where('id', $folder_id)->first();
        if($folder === null && $folder_id !== null) {
            throw new FolderNotFoundException();
        }else if($folder_id !== null) {
            $data = [];
            $data['files'] = File::where('folder_id', $folder_id)->get();
            $data['images'] = Image::where('folder_id', $folder_id)->get();
            $data['folders'] = Folder::where('parent_id', $folder_id)->get();

            return response()->json($data);
        }else{
            $data = [];
            $data['files'] = File::whereNull('folder_id')->get();
            $data['images'] = Image::whereNull('folder_id')->get();
            $data['folders'] = Folder::whereNull('parent_id')->get();

            return response()->json($data);
        }

        return Response::HTTP_BAD_REQUEST;
    }
}