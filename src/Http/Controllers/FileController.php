<?php

namespace Devilwacause\UnboundCore\Http\Controllers;

use Devilwacause\UnboundCore\Exceptions\DatabaseExceptions\DatabaseException;
use Devilwacause\UnboundCore\Exceptions\FileExceptions\FileDatabaseException;
use Devilwacause\UnboundCore\Exceptions\FileExceptions\FileNotFoundException;
use Devilwacause\UnboundCore\Exceptions\FileExceptions\FileWriteException;
use Devilwacause\UnboundCore\Http\Controllers\Traits\FileManagementCommon;
use Devilwacause\UnboundCore\Models\File;
use Devilwacause\UnboundCore\Models\Folder;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Storage;

class FileController extends BaseController
{
    use FileManagementCommon;

    public function __construct() {

    }

    /**
     * @param $fileUUID
     * @return void
     * @throws ImageNotFoundException
     */
    public function show(Request $request, $fileUUID) {
        try {
            $image = Image::where('id', $fileUUID)->first();
        }catch(\Illuminate\Database\QueryException $e) {
            throw new DatabaseException($e->getMessage());
        }
        if($image === null) {
            throw new ImageNotFoundException();
        }else{
            //dd($this->glide);
            $filepath = str_replace('public', '', $image->file_path);
            $this->glide->outputImage($filepath, $request->all());
        }
    }

    public function get($fileUUID) {
        try {
            $image = Image::where('id', $fileUUID)->first();
        }catch(\Illuminate\Database\QueryException $e) {
            throw new DatabaseException($e->getMessage());
        }
        if($image === null) {
            throw new ImageNotFoundException();
        }else{
            return json_encode($image);
        }

    }

    public function create(Request $request) {
        $request->validate([
            'file' => 'required|image',
            'filename' => 'required|string',
            'folder_id' => 'integer',
            'folder_name' => 'string|min:3',
            'width' => 'integer',
            'height' => 'integer',
            'title' => 'string',
            'meta_data' => 'json'
        ]);
        $folder = null;
        $file = $request->file('file');
        $folder_path = null;
        if(isset($request['folder_name'])) {
            //Check for folder.
            $parent_folder = isset($request['folder_id']) ? $request['folder_id'] : null;

            if($parent_folder === null) {
                $folder = Folder::where('folder_name', $request['folder_name'])->whereNull('parent_id')->first();
            }else{
                $folder = Folder::where('folder_name', $request['folder_name'])->where('parent_id', $parent_folder)->first();
            }

            if($folder !== null) {
                //Folder Already Exists!
                //Return folder path
                $folder_path = $this->getFolderPath($folder);
            }else{
                //Create new folder
                //Return folder path
                $data = [];
                $data['folder_name'] = $request['folder_name'];
                $data['parent_id'] = isset($request['folder_id']) ? $request['folder_id'] : null;
                $folder = $this->createFolder($data);
                $folder_path = $this->getFolderPath($folder);
            }
        }else{
            $folder_path = '/';
        }

        //Check for file in the folder path
        $name_to_check = $request['filename'] .'.'. $file->extension();
        $filename = $this->verifyFilename($name_to_check, $folder_path);
        try {
            $this->saveFileToDisk($folder_path, $file, $filename);
        }catch(\Exception $e) {
            throw new ImageWriteException();
        }

        try {
            Image::create([
               'folder_id' => $folder->id,
               'file_path' => $folder_path . $filename,
               'file_name' => $filename,
               'extension' => $file->extension(),
               'width' => isset($request['width']) ? $request['width'] : null,
               'height' => isset($request['height']) ? $request['height'] : null,
               'title' => isset($request['title']) ? $request['title'] : '',
               'meta_data' => isset($request['meta_data']) ? $request['meta_data'] : null,
            ]);
        }catch(\Exception $e) {
            dd($e->getMessage());
            throw new ImageDatabaseException();
        }
    }

}