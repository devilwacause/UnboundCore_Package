<?php

namespace Devilwacause\UnboundCore\Http\Repositories;

use Log;
use Devilwacause\UnboundCore\Exceptions\{
    FileExceptions\FileDatabaseException,
    FileExceptions\FileNotFoundException,
    FileExceptions\FileWriteException,
    FolderExceptions\FolderNotFoundException,
    ImageExceptions\FileDeleteException};

use Devilwacause\UnboundCore\Http\ {
    Interfaces\FileRepositoryInterface
};
use Devilwacause\UnboundCore\Models\ {
    File,
    Folder,
};

use Illuminate\Http\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FileRepository implements FileRepositoryInterface
{
    public function __construct() {

    }

    /**
     * @param $fileUUID
     * @return int|\Symfony\Component\HttpFoundation\BinaryFileResponse
     * @return \Symfony\Component\HttpFoundation\StreamedResponse|\Symfony\Component\HttpFoundation\Response
     * @return FileNotFoundException|FileDatabaseException
     * @throws FileDatabaseException
     * @throws FileNotFoundException
     */
    public function show($fileUUID) : int|\Symfony\Component\HttpFoundation\BinaryFileResponse|
                                          \Symfony\Component\HttpFoundation\StreamedResponse|
                                          \Symfony\Component\HttpFoundation\Response|
                                          FileNotFoundException|FileDatabaseException {
        try {
            $file = File::where('uuid', $fileUUID)->first();
        }catch(\Illuminate\Database\QueryException $e) {
            Log::channel('unbound_file_log')->error('Database Exception while getting file: '.$e->getMessage());
            throw new FileDatabaseException($e->getMessage());
        }
        if($file === null) {
            Log::channel('unbound_file_log')->error('File not found: '.$fileUUID);
            throw new FileNotFoundException();
        }else{
            return response()->file(Storage::url($file->path));
        }
    }

    /**
     * @param Request $request
     * @param $fileUUID
     * @return string|FileDatabaseException|FileNotFoundException
     * @throws FileDatabaseException
     * @throws FileNotFoundException
     */
    public function get(Request $request, $fileUUID) : string|FileDatabaseException|FileNotFoundException {
        try {
            $file = File::where('id', $fileUUID)->first();
        }catch(\Illuminate\Database\QueryException $e) {
            Log::channel('unbound_file_log')->error('Database Exception Finding Image', ['fileUUID' => $fileUUID, 'exception' => $e->getMessage()]);
            throw new FileDatabaseException($e->getMessage());
        }
        if($file === null) {
            Log::channel('unbound_file_log')->info('Image not found', ['fileUUID' => $fileUUID]);
            throw new FileNotFoundException();
        }else{
            return json_encode($file);
        }
    }

    /**
     * @param $fileUUID
     * @return int|Response|\Symfony\Component\HttpFoundation\BinaryFileResponse
     * @return \Symfony\Component\HttpFoundation\StreamedResponse|\Symfony\Component\HttpFoundation\Response
     * @return FileDatabaseException|FileNotFoundException
     * @throws FileDatabaseException
     * @throws FileNotFoundException
     */
    public function download($fileUUID) : int|Response|\Symfony\Component\HttpFoundation\BinaryFileResponse|
                                          \Symfony\Component\HttpFoundation\StreamedResponse|
                                          \Symfony\Component\HttpFoundation\Response|FileDatabaseException|
                                          FileNotFoundException {
        try {
            $file = File::where('uuid', $fileUUID)->first();
        }catch(\Illuminate\Database\QueryException $e) {
            Log::channel('unbound_file_log')->error('Database Exception while getting file: '.$e->getMessage());
            throw new FileDatabaseException($e->getMessage());
        }
        if($file === null) {
            Log::channel('unbound_file_log')->error('File not found: '.$fileUUID);
            throw new FileNotFoundException();
        }else{
            return response()->download(Storage::url($file->path));
        }

    }

    /**
     * @param Request $request
     * @return string|int|FileDatabaseException|FileWriteException
     * @throws FileDatabaseException
     * @throws FileWriteException
     */
    public function create(Request $request) : string|int|FileDatabaseException|FileWriteException {
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
            Log::channel('unbound_file_log')->error('File Upload Error: '.$e->getMessage());
            throw new FileWriteException();
        }
        try {
            File::create([
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
            Log::channel('unbound_file_log')->error('Database Exception Saving File: '.$e->getMessage());
            throw new FileDatabaseException();
        }

        return Response::HTTP_OK;
    }

    /**
     * @param Request $request
     * @return string|int|FileDatabaseException|FileWriteException
     * @throws FileDatabaseException
     * @throws FileNotFoundException
     */
    public function update(Request $request) : string|int|FileDatabaseException|FileWriteException {
        $request->validate([
            'file_id' => 'required|string',
            'title' => 'string',
            'meta_data' => 'json'
        ]);
        try {
            $file = File::where('id', $request['file_id'])->first();
        }catch(\Illuminate\Database\QueryException $e) {
            Log::channel('unbound_file_log')->error('Database Exception Finding File', ['fileUUID' => $request['file_id'], 'exception' => $e->getMessage()]);
            throw new FileDatabaseException($e->getMessage());
        }
        if($file === null) {
            Log::channel('unbound_file_log')->error('File Not Found', ['fileUUID' => $request['file_id']]);
            throw new FileNotFoundException();
        }else{
            $file->title = $request['title'] ?? $file->title;
            $file->meta_data = $request['meta_data'] ?? $file->meta_data;

            try {
                $file->save();
            }catch(\Illuminate\Database\QueryException $e) {
                Log::channel('unbound_file_log')->error('Database Exception Saving File', ['fileUUID' => $request['file_id'], 'exception' => $e->getMessage()]);
                throw new FileDatabaseException($e->getMessage());
            }
        }
        return Response::HTTP_OK;
    }

    /**
     * @param Request $request
     * @return string|int|FileDatabaseException|FileNotFoundException|FileWriteException
     * @throws FileDatabaseException
     * @throws FileNotFoundException
     * @throws FileWriteException
     */
    public function change(Request $request) : string|int|FileDatabaseException|FileNotFoundException|
                                               FileWriteException {
        $request->validate([
            'file' => 'required|image',
            'file_id' => 'required|string',
            'filename' => 'string',
            'title' => 'string',
            'meta_data' => 'json'
        ]);

        try {
            $cfile = File::where('id', $request['file_id'])->first();
        }catch(\Illuminate\Database\QueryException $e) {
            Log::channel('unbound_file_log')->error('Database Exception Finding Image', ['fileUUID' => $request['file_id'], 'exception' => $e->getMessage()]);
            throw new FileDatabaseException($e);
        }
        if($cfile === null) {
            Log::channel('unbound_file_log')->info('Image not found', ['fileUUID' => $request['file_id']]);
            throw new FileNotFoundException();
        }
        $folder = Folder::where('id',$cfile->folder_id)->first();
        $folder_path = $this->getFolderPath($folder);
        $file = $request->file('file');
        $filename = '';
        $current_file_name = $cfile->file_name;

        //Move old file to tmp storage incase the new one fails to save.
        Storage::move($folder_path . $cfile->file_name, "/files/tmp/{$cfile->file_name}");

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
            Log::channel('unbound_file_log')->error('File Upload Error during change: '. $e->getMessage());
            Storage::move("/files/tmp/{$cfile->file_name}", $folder_path);
            throw new FileWriteException();
        }

        $cfile->extension = $file->extension();
        isset($request['title']) ? $cfile->title = $request['title'] : null;
        isset($request['meta_data']) ? $cfile->meta_data = $request['meta_data'] : null;

        $file->file_name = $filename;
        $file->file_path = $folder_path . $filename;

        try {
            $cfile->save();
        }catch(\Illuminate\Database\QueryException $e) {
            //Move old file BACK to correct location
            //Move file back to main
            Log::channel('unbound_file_log')->error('Database Error during file change: '. $e->getMessage());
            Storage::move("/files/tmp/{$current_file_name}", $folder_path);
            throw new FileDatabaseException($e->getMessage());
        }
        //Delete old image permanently
        Storage::delete("/files/tmp/{$current_file_name}");

        return Response::HTTP_OK;
    }

    /**
     * @param Request $request
     * @return string|int|FileDatabaseException|FileNotFoundException|FileWriteException|FolderNotFoundException
     * @throws FileDatabaseException
     * @throws FileNotFoundException
     * @throws FileWriteException
     * @throws FolderNotFoundException
     */
    public function move(Request $request) : string|int|FileDatabaseException|FileNotFoundException|
                                             FileWriteException|FolderNotFoundException {
        $request->validate([
            'file_id' => 'required|string',
            'folder_id' => 'required|integer',
        ]);
        try {
            $file = File::where('id', $request['file_id'])->first();
        }catch(\Illuminate\Database\QueryException $e) {
            Log::channel('unbound_file_log')->error('Database Exception Finding File', ['fileUUID' => $request['file_id'], 'exception' => $e->getMessage()]);
            throw new FileDatabaseException($e->getMessage());
        }
        if($file === null) {
            Log::channel('unbound_file_log')->info('File not found', ['fileUUID' => $request['file_id']]);
            throw new FileNotFoundException();
        }
        try {
            $folder = Folder::where('id', $request['folder_id'])->first();
        }catch(\Illuminate\Database\QueryException $e) {
            Log::channel('unbound_file_log')->error('Database Exception Finding Folder', ['folder_id' => $request['folder_id'], 'exception' => $e->getMessage()]);
            throw new FolderNotFoundException($e->getMessage());
        }
        if($folder === null) {
            Log::channel('unbound_file_log')->info('Folder not found during move', ['fileUUID' => $request['file_id'], 'folder_id' => $request['folder_id']]);
            throw new FolderNotFoundException();
        }
        $new_file_path = $this->getFolderPath($folder);
        $filenameVerify = $this->verifyFilename($file->file_name, $new_file_path);
        $storage_position = $new_file_path . $filenameVerify;

        try {
            Storage::move($file->file_path, $storage_position);
        }catch(\Exception $e) {
            Log::channel('unbound_file_log')->error('Failed to move file : ', ['fileUUID' => $request['file_id'], 'folder_id' => $request['folder_id'], 'exception' => $e->getMessage()]);
            throw new FileWriteException();
        }
        $file->folder_id = $folder->id;
        $file->file_path = $storage_position;
        $file->file_name = $filenameVerify;
        try {
            $file->save();
        }catch(\Illuminate\Database\QueryException $e) {
            Log::channel('unbound_file_log')->error('Database Error during file move: '. $e->getMessage());
            throw new FileDatabaseException($e->getMessage());
        }

        return Response::HTTP_OK;
    }

    /**
     * @param Request $request
     * @return string|int|FileDatabaseException|FileNotFoundException|FileWriteException|FolderNotFoundException
     * @throws FileDatabaseException
     * @throws FileNotFoundException
     * @throws FileWriteException
     * @throws FolderNotFoundException
     */
    public function copy(Request $request) : string|int|FileDatabaseException|FileNotFoundException|
                                             FileWriteException|FolderNotFoundException {
        $request->validate([
            'file_id' => 'required|string',
            'folder_id' => 'required|integer',
        ]);

        try {
            $file = File::where('id', $request['file_id'])->first();
        }catch(\Illuminate\Database\QueryException $e) {
            Log::channel('unbound_file_log')->error('Database Exception Finding File', ['fileUUID' => $request['file_id'], 'exception' => $e->getMessage()]);
            throw new FileDatabaseException($e->getMessage());
        }
        if($file === null) {
            Log::channel('unbound_file_log')->info('File not found', ['fileUUID' => $request['file_id']]);
            throw new FileNotFoundException();
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
        $filenameVerify = $this->verifyFilename($file->file_name, $new_file_path);
        $storage_position = $new_file_path . $filenameVerify;

        try {
            Storage::copy($file->file_path, $storage_position);
        }catch(\Exception $e) {
            Log::channel('unbound_file_log')->error('Failed to copy file : ', ['fileUUID' => $request['file_id'], 'folder_id' => $request['folder_id'], 'exception' => $e->getMessage()]);
            throw new FileWriteException();
        }
        $new_file = new File();
        $new_file->folder_id = $folder->id;
        $new_file->file_path = $new_file_path . $filenameVerify;
        $new_file->file_name = $filenameVerify;
        $new_file->extension = $file->extension;
        $new_file->title = $file->title;
        $new_file->meta_data = $file->meta_data;

        try {
            $new_file->save();
        }catch(\Illuminate\Database\QueryException $e) {
            Log::channel('unbound_file_log')->error('Database Exception Saving File', ['fileUUID' => $request['file_id'], 'folder_id' => $request['folder_id'], 'exception' => $e->getMessage()]);
            throw new FileDatabaseException($e->getMessage());
        }

        return Response::HTTP_OK;
    }

    /**
     * @param Request $request
     * @return string|int|FileDatabaseException|FileDeleteException|FileNotFoundException
     * @throws FileDatabaseException
     * @throws FileDeleteException
     * @throws FileNotFoundException
     */
    public function remove(Request $request) : string|int|FileDatabaseException|FileDeleteException|
                                               FileNotFoundException {
        $request->validate([
            'file_id' => 'required|string',
        ]);
        try {
            $image = File::where('id', $request['file_id'])->first();
        }catch(\Illuminate\Database\QueryException $e) {
            Log::channel('unbound_file_log')->error('Database Exception Finding File', ['fileUUID' => $request['file_id'], 'exception' => $e->getMessage()]);
            throw new FileDatabaseException($e->getMessage());
        }
        if($image === null) {
            Log::channel('unbound_file_log')->error('File Not Found', ['fileUUID' => $request['file_id']]);
            throw new FileNotFoundException();
        }
        try {
            $this->removeFileFromDisk($image->file_path, true);
        }catch(\Exception $e) {
            Log::channel('unbound_file_log')->error('Error removing file from disk', ['fileUUID' => $request['file_id'], 'exception' => $e->getMessage()]);
            throw new FileDeleteException();
        }
        try {
            $image->delete();
        }catch(\Illuminate\Database\QueryException $e) {
            Log::channel('unbound_file_log')->error('Database Exception Deleting File', ['fileUUID' => $request['file_id'], 'exception' => $e->getMessage()]);
            throw new FileDatabaseException($e->getMessage());
        }
        return Response::HTTP_OK;
    }
}