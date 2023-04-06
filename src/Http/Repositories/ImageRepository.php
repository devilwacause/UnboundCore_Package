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

    public function validateUpdate(Request $request) {
        $request->validate([
            'file_id' => 'required|string',
            'title' => 'string',
            'width' => 'integer',
            'height' => 'integer',
            'meta_data' => 'json'
        ]);
    }

    public function validateChange(Request $request) {
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
    }

    public function validateMoveCopy(Request $request) {
        $request->validate([
            'file_id' => 'required|string',
            'folder_id' => 'required|integer',
        ]);
    }

    public function validateRemove(Request $request) {
        $request->validate([
            'file_id' => 'required|string',
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
     * @param array $data
     * @return int|ImageDatabaseException
     * @throws ImageDatabaseException
     */
    public function update(Array $data) : int|ImageDatabaseException  {
        try {
            Image::where('id', $data['file_id'])->update([
                'title' => $data['title'],
                'width' => $data['width'],
                'height' => $data['height'],
                'meta_data' => $data['meta_data'],
            ]);
        }catch(\Illuminate\Database\QueryException $e) {
            Log::channel('unbound_file_log')->error('Database Exception Updating Image',
                ['fileUUID' => $data['file_id'], 'exception' => $e->getMessage()]);

            throw new ImageDatabaseException($e->getMessage());
        }
        return Response::HTTP_OK;
    }

    /**
     * @param array $data
     * @return int|ImageDatabaseException
     * @throws ImageDatabaseException
     */
    public function change(Array $data) : int|ImageDatabaseException {
        try {
            Image::where('id', $data['file_id'])->update([
                'file_name' => $data['filename'],
                'file_path' => $data['folder_path'] . $data['filename'],
                'extension' => $data['extension'],
                'title' => $data['title'],
                'width' => $data['width'],
                'height' => $data['height'],
                'meta_data' => $data['meta_data']
            ]);
        }catch(\Illuminate\Database\QueryException $e) {
            Log::channel('unbound_file_log')->error('Database Error during image change: '. $e->getMessage());
            throw new ImageDatabaseException($e->getMessage());
        }
        return Response::HTTP_OK;
    }

    /**
     * @param array $data
     * @return int|ImageDatabaseException
     * @throws ImageDatabaseException
     */
    public function move(Array $data) : int|ImageDatabaseException  {
        try {
            Image::where('id', $data['file_id'])->update([
                'folder_id' => $data['folder_id'],
                'file_path' => $data['file_path'],
                'file_name' => $data['filename'],
            ]);
        }catch(\Illuminate\Database\QueryException $e) {
            Log::channel('unbound_file_log')->error('Database Error during image move: '. $e->getMessage());
            throw new ImageDatabaseException($e->getMessage());
        }
        return Response::HTTP_OK;
    }

    /**
     * @param array $data
     * @return int|ImageDatabaseException
     * @throws ImageDatabaseException
     */
    public function copy(Array $data) : int|ImageDatabaseException {
        try {
            Image::create([
                'folder_id' => $data['folder_id'],
                'file_path' => $data['file_path'],
                'file_name' => $data['file_name'],
                'extension' => $data['extension'],
                'width' => $data['width'],
                'height' => $data['height'],
                'title' => $data['title'],
                'meta_data' => $data['meta_data'],
            ]);
        }catch(\Illuminate\Database\QueryException $e) {
            Log::channel('unbound_file_log')->error('Database Exception Saving Image', ['fileUUID' => $data['file_id'],
                'folder_id' => $data['folder_id'], 'exception' => $e->getMessage()]);
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
    public function remove (Array $data) : string|int|ImageDatabaseException|ImageDeleteException {
        try {
            Image::where('id', $data['file_id'])->delete();
        }catch(\Illuminate\Database\QueryException $e) {
            Log::channel('unbound_file_log')->error('Database Exception Deleting Image', ['fileUUID' => $request['file_id'], 'exception' => $e->getMessage()]);
            throw new ImageDatabaseException($e->getMessage());
        }
        return Response::HTTP_OK;
    }
}