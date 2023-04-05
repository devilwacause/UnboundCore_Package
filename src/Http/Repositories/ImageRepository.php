<?php

namespace Devilwacause\UnboundCore\Http\Repositories;

use Devilwacause\UnboundCore\Exceptions\ {
    DatabaseExceptions\DatabaseException,
    FolderExceptions\FolderNotFoundException,
    ImageExceptions\ImageDatabaseException,
    ImageExceptions\ImageDeleteException,
    ImageExceptions\ImageNotFoundException,
    ImageExceptions\ImageWriteException,
};
use Devilwacause\UnboundCore\Models\ {
    Image,
    Folder,
};
use Devilwacause\UnboundCore\Http\ {
    Interfaces\ImageRepositoryInterface,
    Traits\FileManagementCommon,
};


use Illuminate\Http\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ImageRepository implements ImageRepositoryInterface
{
    use FileManagementCommon;

    public function findByUUID($UUID) {
        return Image::findOrFail($UUID);
    }
    public function validateCreate(Request $request) {
        $request->validate([
            'file' => 'required_without:file_base64|image',
            'file_base64' => 'required_without:file|base64image',
            'filename' => 'required|string',
            'folder_id' => 'integer|nullable',
            'folder_name' => 'string|min:3',
            'width' => 'integer',
            'height' => 'integer',
            'title' => 'string',
            'meta_data' => 'json'
        ]);
    }

    /**
     * @param Array $data
     * @return int|ImageDatabaseException
     * @throws ImageDatabaseException
     * @throws DatabaseException
     */
    public function create(Array $data) : int|ImageDatabaseException {
        try {
            Image::create([
                'folder_id' => $data['folder_id'],
                'file_path' => $data['file_path'],
                'file_name' => $data['file_name'],
                'extension' => $data['extension'],
                'width' => isset($data['width']) ? $data['width'] : null,
                'height' => isset($data['height']) ? $data['height'] : null,
                'title' => isset($data['title']) ? $data['title'] : '',
                'meta_data' => isset($data['meta_data']) ? $data['meta_data'] : null,
            ]);
        }catch(\Exception $e) {
            Log::channel('unbound_file_log')->error('Database Exception Saving Image', ['exception' => $e->getMessage()]);
            throw new ImageDatabaseException($e->getMessage());
        }
        Return Response::HTTP_CREATED;
    }

    /**
     * @param Request $request
     * @return string|int|ImageDatabaseException|ImageNotFoundException
     * @throws ImageDatabaseException
     * @throws ImageNotFoundException
     */
    public function update(Request $request) : string|int|ImageDatabaseException|ImageNotFoundException  {
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
            Log::channel('unbound_file_log')->error('Database Exception Finding Image', ['fileUUID' => $request['file_id'], 'exception' => $e->getMessage()]);
            throw new ImageDatabaseException($e);
        }
        if($image === null) {
            Log::channel('unbound_file_log')->info('Image not found', ['fileUUID' => $request['file_id']]);
            throw new ImageNotFoundException();
        }else{
            $image->title = $request['title'];
            $image->width = $request['width'];
            $image->height = $request['height'];
            $image->meta_data = $request['meta_data'];

            try {
                $image->save();
            }catch(\Illuminate\Database\QueryException $e) {
                Log::channel('unbound_file_log')->error('Database Exception Saving Image', ['fileUUID' => $request['file_id'], 'exception' => $e->getMessage()]);
                throw new ImageDatabaseException($e->getMessage());
            }
        }
        return Response::HTTP_OK;
    }

    /**
     * @param Request $request
     * @return string|int|ImageDatabaseException|ImageNotFoundException|ImageWriteException
     * @throws ImageDatabaseException
     * @throws ImageNotFoundException
     * @throws ImageWriteException
     */
    public function change(Request $request) : string|int|ImageDatabaseException|ImageNotFoundException|ImageWriteException  {
        $request->validate([
            'file' => 'required_without:file_base64|image',
            'file_base64' => 'required_without:file|base64image',
            'file_id' => 'required|string',
            'filename' => 'string',
            'title' => 'string',
            'width' => 'integer',
            'height' => 'integer',
            'meta_data' => 'json'
        ]);
        try {
            $image = Image::where('id', $request['file_id'])->first();
        }catch(\Illuminate\Database\QueryException $e) {
            Log::channel('unbound_file_log')->error('Database Exception Finding Image', ['fileUUID' => $request['file_id'], 'exception' => $e->getMessage()]);
            throw new ImageDatabaseException($e);
        }
        if($image === null) {
            Log::channel('unbound_file_log')->info('Image not found', ['fileUUID' => $request['file_id']]);
            throw new ImageNotFoundException();
        }
        $folder = Folder::where('id',$image->folder_id)->first();
        $folder_path = $this->getFolderPath($folder);
        $file = $request->file('file');
        $filename = '';
        $current_file_name = $image->file_name;

        if($request->file('file') !== null) {

            $file = $request->file('file');
            $extension = $file->extension();
        }else{
            $tmp_file = $this->convertB64ToFile($request['file_base64']);
            $extension = $tmp_file['extension'];
            $file = $tmp_file['file'];
        }


        //Move old file to tmp storage incase the new one fails to save.
        Storage::move($folder_path . $image->file_name, "/images/tmp/{$image->file_name}");

        if(isset($request['filename'])) {
            $filename = $request['filename'] .'.' .$extension;
            $filename = $this->verifyFilename($filename, $folder_path);
        }else{
            $filename = $current_file_name;
        }
        try {
            $this->saveFileToDisk($folder_path, $file, $filename);
        }catch(\Exception $e) {
            //Move file back to main
            Log::channel('unbound_file_log')->error('Image Upload Error during change: '. $e->getMessage());
            Storage::move("/images/tmp/{$image->file_name}", $folder_path);
            throw new ImageWriteException();
        }

        $image->extension = $extension;
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
            Log::channel('unbound_file_log')->error('Database Error during image change: '. $e->getMessage());
            Storage::move("/images/tmp/{$current_file_name}", $folder_path);
            throw new ImageDatabaseException($e->getMessage());
        }
        //Delete old image permanently
        Storage::delete("/images/tmp/{$current_file_name}");

        return Response::HTTP_OK;
    }

    /**
     * @param Request $request
     * @return string|int|FolderNotFoundException|ImageNotFoundException|ImageWriteException
     * @throws FolderNotFoundException
     * @throws ImageDatabaseException
     * @throws ImageNotFoundException
     * @throws ImageWriteException
     */
    public function move(Request $request) : string|int|FolderNotFoundException|ImageNotFoundException|ImageWriteException  {
        $request->validate([
            'file_id' => 'required|string',
            'folder_id' => 'required|integer',
        ]);
        try {
            $image = Image::where('id', $request['file_id'])->first();
        }catch(\Illuminate\Database\QueryException $e) {
            Log::channel('unbound_file_log')->error('Database Exception Finding Image', ['fileUUID' => $request['file_id'], 'exception' => $e->getMessage()]);
            throw new ImageDatabaseException($e->getMessage());
        }
        if($image === null) {
            Log::channel('unbound_file_log')->info('Image not found', ['fileUUID' => $request['file_id']]);
            throw new ImageNotFoundException();
        }
        try {
            $folder = Folder::where('id', $request['folder_id'])->first();
        }catch(\Illuminate\Database\QueryException $e) {
            Log::channel('unbound_file_log')->error('Database Exception Finding Folder', ['folder_id' => $request['folder_id'], 'exception' => $e->getMessage()]);
            throw new ImageDatabaseException($e->getMessage());
        }
        if($folder === null) {
            Log::channel('unbound_file_log')->info('Folder not found during move', ['fileUUID' => $request['file_id'], 'folder_id' => $request['folder_id']]);
            throw new FolderNotFoundException();
        }
        $new_file_path = $this->getFolderPath($folder);
        $filenameVerify = $this->verifyFilename($image->file_name, $new_file_path);
        $storage_position = $new_file_path . $filenameVerify;

        try {
            Storage::move($image->file_path, $storage_position);
        }catch(\Exception $e) {
            Log::channel('unbound_file_log')->error('Failed to move image : ', ['fileUUID' => $request['file_id'], 'folder_id' => $request['folder_id'], 'exception' => $e->getMessage()]);
            throw new ImageWriteException();
        }
        $image->folder_id = $folder->id;
        $image->file_path = $storage_position;
        $image->file_name = $filenameVerify;
        try {
            $image->save();
        }catch(\Illuminate\Database\QueryException $e) {
            Log::channel('unbound_file_log')->error('Database Error during image move: '. $e->getMessage());
            throw new ImageDatabaseException($e->getMessage());
        }

        return Response::HTTP_OK;
    }

    /**
     * @param Request $request
     * @return string|int|FolderNotFoundException|ImageNotFoundException|ImageWriteException
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
        try {
            $image = Image::where('id', $request['file_id'])->first();
        }catch(\Illuminate\Database\QueryException $e) {
            Log::channel('unbound_file_log')->error('Database Exception Finding Image', ['fileUUID' => $request['file_id'], 'exception' => $e->getMessage()]);
            throw new ImageDatabaseException($e->getMessage());
        }
        if($image === null) {
            Log::channel('unbound_file_log')->info('Image not found', ['fileUUID' => $request['file_id']]);
            throw new ImageNotFoundException();
        }
        try {
            $folder = Folder::where('id', $request['folder_id'])->first();
        }catch(\Illuminate\Database\QueryException $e) {
            Log::channel('unbound_file_log')->error('Database Exception Finding Folder', ['folder_id' => $request['folder_id'], 'exception' => $e->getMessage()]);
            throw new FolderNotFoundException();
        }
        if($folder === null) {
            Log::channel('unbound_file_log')->info('Folder not found during copy', ['fileUUID' => $request['file_id'], 'folder_id' => $request['folder_id']]);
            throw new FolderNotFoundException();
        }
        $new_file_path = $this->getFolderPath($folder);
        $filenameVerify = $this->verifyFilename($image->file_name, $new_file_path);
        $storage_position = $new_file_path . $filenameVerify;

        try {
            Storage::copy($image->file_path, $storage_position);
        }catch(\Exception $e) {
            Log::channel('unbound_file_log')->error('Failed to copy image : ', ['fileUUID' => $request['file_id'], 'folder_id' => $request['folder_id'], 'exception' => $e->getMessage()]);
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
            Log::channel('unbound_file_log')->error('Database Exception Saving Image', ['fileUUID' => $request['file_id'], 'folder_id' => $request['folder_id'], 'exception' => $e->getMessage()]);
            throw new ImageDatabaseException($e->getMessage());
        }

        return Response::HTTP_OK;
    }

    /**
     * @param Request $request
     * @return string|int|ImageDatabaseException|ImageDeleteException
     * @throws ImageDatabaseException
     * @throws ImageDeleteException
     * @throws ImageNotFoundException
     */
    public function remove (Request $request) : string|int|ImageDatabaseException|ImageDeleteException {
        $request->validate([
            'file_id' => 'required|string',
        ]);
        try {
            $image = Image::where('id', $request['file_id'])->first();
        }catch(\Illuminate\Database\QueryException $e) {
            Log::channel('unbound_file_log')->error('Database Exception Finding Image', ['fileUUID' => $request['file_id'], 'exception' => $e->getMessage()]);
            throw new ImageDatabaseException($e->getMessage());
        }
        if($image === null) {
            Log::channel('unbound_file_log')->info('Image not found during removal', ['fileUUID' => $request['file_id']]);
            throw new ImageNotFoundException();
        }
        try {
            $this->removeFileFromDisk($image->file_path, true);
        }catch(\Exception $e) {
            Log::channel('unbound_file_log')->error('Failed to remove image : ', ['fileUUID' => $request['file_id'], 'exception' => $e->getMessage()]);
            throw new ImageDeleteException();
        }
        try {
            $image->delete();
        }catch(\Illuminate\Database\QueryException $e) {
            Log::channel('unbound_file_log')->error('Database Exception Deleting Image', ['fileUUID' => $request['file_id'], 'exception' => $e->getMessage()]);
            throw new ImageDatabaseException($e->getMessage());
        }
        return Response::HTTP_OK;
    }
}