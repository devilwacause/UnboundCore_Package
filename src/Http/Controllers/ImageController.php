<?php

namespace Devilwacause\UnboundCore\Http\Controllers;

use Devilwacause\UnboundCore\Exceptions\DatabaseExceptions\DatabaseException;
use Devilwacause\UnboundCore\Exceptions\FolderExceptions\FolderNotFoundException;
use Devilwacause\UnboundCore\Exceptions\ImageExceptions\ImageDatabaseException;
use Devilwacause\UnboundCore\Exceptions\ImageExceptions\ImageDeleteException;
use Devilwacause\UnboundCore\Exceptions\ImageExceptions\ImageNotFoundException;
use Devilwacause\UnboundCore\Exceptions\ImageExceptions\ImageWriteException;
use Devilwacause\UnboundCore\Http\Controllers\Interfaces\FileInterface;
use Devilwacause\UnboundCore\Http\Controllers\Traits\FileManagementCommon;
use Devilwacause\UnboundCore\Models\Image;
use Devilwacause\UnboundCore\Models\Folder;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Storage;
use League\Glide\ServerFactory;
use League\Glide\Server;
use League\Glide\Responses\LaravelResponseFactory as GlideResponse;

class ImageController extends BaseController implements FileInterface
{
    use FileManagementCommon;

    private \League\Glide\Server $glide;
    private \League\Glide\Responses\LaravelResponseFactory $responseFactory;
    public function __construct() {
        $this->responseFactory = new GlideResponse(app('request'));
        $this->glide = ServerFactory::create([
            'source' => config('glide.SOURCE'),
            'cache' => config('glide.CACHE'),
            'response' => $this->responseFactory,
        ]);
    }

    /**
     * Return the image from the glide provider
     * @param Request $request
     * @param $fileUUID
     * @return Response
     * @throws ImageDatabaseException
     * @throws ImageNotFoundException
     *
     */
    public function show(Request $request, $fileUUID) : int|GlideResponse|ImageDatabaseException|ImageNotFoundException {
        try {
            $image = Image::where('id', $fileUUID)->first();
        }catch(\Illuminate\Database\QueryException $e) {
            throw new ImageDatabaseException($e->getMessage());
        }
        if($image === null) {
            throw new ImageNotFoundException();
        }else{
            $filepath = str_replace('public', '', $image->file_path);
            $this->glide->outputImage($filepath, $request->all());
        }

        return Response::HTTP_BAD_REQUEST;
    }

    /**
     * Get the database record for an image
     *
     * @param $fileUUID
     * @return false|string
     * @throws ImageDatabaseException
     * @throws ImageNotFoundException
     */
    public function get($fileUUID) : string|false|ImageDatabaseException|ImageNotFoundException {
        try {
            $image = Image::where('id', $fileUUID)->first();
        }catch(\Illuminate\Database\QueryException $e) {
            throw new ImageDatabaseException($e->getMessage());
        }
        if($image === null) {
            throw new ImageNotFoundException();
        }else{
            return json_encode($image);
        }

    }

    /**
     * Store image and create new database record
     * Requires "Accept : application/json" for validation purposes
     * Saves image to storage and adds to database
     *
     * @param Request $request
     * @return int
     * @throws DatabaseException
     * @throws ImageDatabaseException
     * @throws ImageWriteException
     */
    public function create(Request $request) : int|DatabaseException|ImageDatabaseException|ImageWriteException {
        $v = $request->validate([
            'file' => 'required_without:file_base64|image',
            'file_base64' => 'required_without:file|base64image',
            'filename' => 'required|string',
            'folder_id' => 'integer',
            'folder_name' => 'string|min:3',
            'width' => 'integer',
            'height' => 'integer',
            'title' => 'string',
            'meta_data' => 'json'
        ]);
        $folder = null;
        if($request->file('file') !== null) {

            $file = $request->file('file');
            $extension = $file->extension();
            $name_to_check = $request['filename'] .'.'. $extension;
        }else{
            $tmp_file = $this->convertB64ToFile($request['file_base64']);
            $name_to_check = $request['filename'] .'.'. $tmp_file['extension'];
            $extension = $tmp_file['extension'];
            $file = $tmp_file['file'];
        }
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
            if(isset($request->folder_id)) {
                $folder = Folder::where('id', $request->folder_id)->first();
                if($folder === null) {
                    $folder_path = '/';
                }else{
                    $folder_path = $this->getFolderPath($folder);
                }
            }else {
                $folder_path = '/';
            }
        }

        //Check for file in the folder path
        $filename = $this->verifyFilename($name_to_check, $folder_path);
        try {
            $this->saveFileToDisk($folder_path, $file, $filename);
        }catch(\Exception $e) {
            throw new ImageWriteException();
        }

        try {
            Image::create([
               'folder_id' => $folder !== null ? $folder->id : null,
               'file_path' => $folder_path . $filename,
               'file_name' => $filename,
               'extension' => $extension,
               'width' => isset($request['width']) ? $request['width'] : null,
               'height' => isset($request['height']) ? $request['height'] : null,
               'title' => isset($request['title']) ? $request['title'] : '',
               'meta_data' => isset($request['meta_data']) ? $request['meta_data'] : null,
            ]);
        }catch(\Exception $e) {
            throw new ImageDatabaseException($e->getMessage());
        }

        Return Response::HTTP_OK;
    }

    /**
     * Update the database data about the image
     * Requires "Accept : application/json" for validation purposes
     * Updates image record in the database
     *
     * @param Request $request
     * @return int
     * @throws ImageDatabaseException
     * @throws ImageNotFoundException
     */
    public function update(Request $request) : string|int|ImageDatabaseException|ImageNotFoundException {
        $request->validate([
            'file_id' => 'required|string',
            'title' => 'string',
            'width' => 'integer',
            'height' => 'integer',
            'meta_data' => 'json'
        ]);
        try {
            $image = Image::where('id', $request['file_id'])->first();
        }catch(\Illuminate\Database\QueryException $e) {
            throw new ImageDatabaseException($e);
        }
        if($image === null) {
            throw new ImageNotFoundException();
        }else{
            $image->title = $request['title'];
            $image->width = $request['width'];
            $image->height = $request['height'];
            $image->meta_data = $request['meta_data'];

            try {
                $image->save();
            }catch(\Illuminate\Database\QueryException $e) {
                throw new ImageDatabaseException($e->getMessage());
            }
        }
        return Response::HTTP_OK;
    }

    /**
     * Change the image that is stored
     * Requires "Accept : application/json" for validation purposes
     * If using "PUT" - must add Param ?_method=PUT to POST request - Laravel Requirement
     *
     * @param Request $request
     * @return int
     * @throws ImageDatabaseException
     * @throws ImageNotFoundException
     * @throws ImageWriteException
     */
    public function change(Request $request) : string|int|ImageDatabaseException|ImageNotFoundException|ImageWriteException {
        $request->validate([
            'file' => 'required|image',
            'file_id' => 'required|string',
            'filename' => 'string',
            'title' => 'string',
            'width' => 'integer',
            'height' => 'integer',
            'meta_data' => 'json'
        ]);
        $image = Image::where('id', $request['file_id'])->first();
        if($image === null) {
            throw new ImageNotFoundException();
        }
        $folder = Folder::where('id',$image->folder_id)->first();
        $folder_path = $this->getFolderPath($folder);
        $file = $request->file('file');
        $filename = '';
        $current_file_name = $image->file_name;

        //Move old file to tmp storage incase the new one fails to save.
        Storage::move($folder_path . $image->file_name, "/images/tmp/{$image->file_name}");

        if(isset($request['filename'])) {
            $filename = $request['filename'] .'.' .$file->extension();
            $filename = $this->verifyFilename($filename, $folder_path);
        }else{
            $filename = $current_file_name;
        }
        try {
            $this->saveFileToDisk($folder_path, $file, $filename);
        }catch(\Exception $e) {
            //Move file back to main
            Storage::move("/images/tmp/{$image->file_name}", $folder_path);
            throw new ImageWriteException();
        }

        $image->extension = $file->extension();
        isset($request['title']) ? $image->title = $request['title'] : null;
        isset($request['width']) ? $image->width = $request['width'] : null;
        isset($request['height']) ? $image->height = $request['height'] : null;
        isset($request['meta_data']) ? $image->meta_data = $request['meta_data'] : null;

        $image->file_name = $filename;
        $image->file_path = $folder_path . $filename;

        try {
            $image->save();
        }catch(\Illuminate\Database\QueryException $e) {
            //Move old file BACK to correct location
            //Move file back to main
            Storage::move("/images/tmp/{$current_file_name}", $folder_path);
            throw new ImageDatabaseException($e->getMessage());
        }
        //Delete old image permanently
        Storage::delete("/images/tmp/{$current_file_name}");

        return Response::HTTP_OK;
    }

    /**
     * Move image to another folder and update database record
     * Requires "Accept : application/json" for validation purposes
     * If using "PUT" - must add Param ?_method=PUT to POST request - Laravel Requirement
     *
     * @param Request $request
     * @return int
     * @throws FolderNotFoundException
     * @throws ImageDatabaseException
     * @throws ImageNotFoundException
     * @throws ImageWriteException
     */
    public function move(Request $request) : string|int|FolderNotFoundException|ImageNotFoundException|ImageWriteException {
        $request->validate([
           'file_id' => 'required|string',
           'folder_id' => 'required|integer',
        ]);
        $image = Image::where('id', $request['file_id'])->first();
        if($image === null) {
            throw new ImageNotFoundException();
        }
        $folder = Folder::where('id', $request['folder_id'])->first();
        if($folder === null) {
            throw new FolderNotFoundException();
        }
        $new_file_path = $this->getFolderPath($folder);
        $filenameVerify = $this->verifyFilename($image->file_name, $new_file_path);
        $storage_position = $new_file_path . $filenameVerify;

        try {
            Storage::move($image->file_path, $storage_position);
        }catch(\Exception $e) {
            throw new ImageWriteException();
        }
        $image->folder_id = $folder->id;
        $image->file_path = $storage_position;
        $image->file_name = $filenameVerify;
        try {
            $image->save();
        }catch(\Illuminate\Database\QueryException $e) {
            throw new ImageDatabaseException($e->getMessage());
        }

        return Response::HTTP_OK;
    }

    /**
     * Copy file to another folder
     * Requires "Accept : application/json" for validation purposes
     * If using "PUT" - must add Param ?_method=PUT to POST request - Laravel Requirement
     *
     * @param Request $request
     * @return int
     * @throws FolderNotFoundException
     * @throws ImageDatabaseException
     * @throws ImageNotFoundException
     * @throws ImageWriteException
     */
    public function copy(Request $request) : string|int|FolderNotFoundException|ImageNotFoundException|ImageWriteException {
        $request->validate([
            'file_id' => 'required|string',
            'folder_id' => 'required|integer',
        ]);
        $image = Image::where('id', $request['file_id'])->first();
        if($image === null) {
            throw new ImageNotFoundException();
        }
        $folder = Folder::where('id', $request['folder_id'])->first();
        if($folder === null) {
            throw new FolderNotFoundException();
        }
        $new_file_path = $this->getFolderPath($folder);
        $filenameVerify = $this->verifyFilename($image->file_name, $new_file_path);
        $storage_position = $new_file_path . $filenameVerify;

        try {
            Storage::copy($image->file_path, $storage_position);
        }catch(\Exception $e) {
            throw new ImageWriteException();
        }
        $new_image = new Image();
        $new_image->folder_id = $folder->id;
        $new_image->file_path = $new_file_path . $filenameVerify;
        $new_image->file_name = $filenameVerify;
        $new_image->extension = $image->extension;
        $new_image->title = $image->title;
        $new_image->width = $image->width;
        $new_image->height = $image->height;
        $new_image->meta_data = $image->meta_data;

        try {
            $new_image->save();
        }catch(\Illuminate\Database\QueryException $e) {
            throw new ImageDatabaseException($e->getMessage());
        }

        return Response::HTTP_OK;
    }

    /**
     * Requires "Accept : application/json" for validation purposes
     * Removes image from storage and database.  Clears cached images
     *
     * @param Request $request
     * @return int
     * @throws ImageDatabaseException
     * @throws ImageDeleteException
     * @throws ImageNotFoundException
     */
    public function remove(Request $request) : string|int|ImageDatabaseException|ImageDeleteException {
        $request->validate([
           'file_id' => 'required|string',
        ]);
        try {
            $image = Image::where('id', $request['file_id'])->first();
        }catch(\Illuminate\Database\QueryException $e) {
            throw new ImageDatabaseException($e->getMessage());
        }
        if($image === null) {
            throw new ImageNotFoundException();
        }
        try {
            $this->removeFileFromDisk($image->file_path, true);
        }catch(\Exception $e) {
            throw new ImageDeleteException();
        }
        try {
            $image->delete();
        }catch(\Illuminate\Database\QueryException $e) {
            throw new ImageDatabaseException($e->getMessage());
        }
        return Response::HTTP_OK;
    }
}