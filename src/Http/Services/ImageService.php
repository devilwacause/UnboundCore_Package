<?php

namespace Devilwacause\UnboundCore\Http\Services;

use Devilwacause\UnboundCore\Exceptions\ImageExceptions\ImageDatabaseException;
use Devilwacause\UnboundCore\Exceptions\ImageExceptions\ImageWriteException;
use Devilwacause\UnboundCore\Http\Interfaces\FolderRepositoryInterface;
use Devilwacause\UnboundCore\Http\Interfaces\ImageRepositoryInterface;
use Devilwacause\UnboundCore\Http\Traits\FileManagementCommon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use League\Glide\ {
    ServerFactory,
    Server,
    Responses\LaravelResponseFactory as GlideResponse
};
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\File\File;

class ImageService
{
    use FileManagementCommon;

    private $glide;
    private $responseFactory;
    private $imageRepository;
    private $folderRepository;

    /**
     * @param ImageRepositoryInterface $imageRepository
     * @param FolderRepositoryInterface $folderRepository
     */
    public function __construct(ImageRepositoryInterface $imageRepository, FolderRepositoryInterface $folderRepository) {
        $this->imageRepository = $imageRepository;
        $this->folderRepository = $folderRepository;
        $this->responseFactory = new GlideResponse(app('request'));
        $this->glide = ServerFactory::create([
            'source' => config('glide.SOURCE'),
            'cache' => config('glide.CACHE'),
            'response' => $this->responseFactory,
        ]);
    }

    public function show(Request $request, $fileUUID) : int|string|GlideResponse {
        try {
           $image = $this->imageRepository->findByUUID($fileUUID);
        }catch(\Exception $e) {
            return $e->getMessage();
        }
        $filepath = str_replace('public', '', $image->file_path);
        try {
            return $this->glide->outputImage($filepath, $request->all());
        }catch(\Exception $e) {
            Log::channel('ubound_image_log')->error('Glide failed to return file',
                ['fileUUID' => $image->id]);
            return Response::HTTP_INTERNAL_SERVER_ERROR;
        }
    }

    public function get(Request $request, $fileUUID) : string {
        try {
            $image = $this->imageRepository->findByUUID($fileUUID);
        }catch(\Exception $e) {
            return $e->getMessage();
        }
        return json_encode($image);
    }

    public function create(Request $request) {
        $data = $request->all();

        //Validate the request
        try {
            $this->imageRepository->validateCreate($request);
        }catch(\Exception $e) {
            return $e->getMessage();
        }

        $folder = null;
        $parent_folder = null;
        $folder_path = null;

        //Check for folder
        if(isset($request['folder_id'])) {
            $findFolder = $this->folderRepository->findById($request['folder_id']);
            if($findFolder !== null && isset($request['folder_name'])) {
                $parent_folder = $findFolder;
            }else{
                $folder = $findFolder;
            }
        }
        //Check for folder with parent id
        if(isset($request['folder_name'])) {
            $findFolder = $this->folderRepository->findByNameAndParent($request['folder_name'], $parent_folder->id);
            if($findFolder !== null) {
                $folder = $findFolder;
            }
        }
        //See if we can create folder
        if($folder === null) {
            $folder_name = isset($request['folder_name']) ? $request['folder_name'] : null;
            $parent_id = $parent_folder !== null ? $parent_folder->id : null;
            $folder = $this->folderRepository->createFolder($folder_name, $parent_id);
        }
        //Retrieve Folder Path
        if($folder === null) {
            $folder_path = 'public/';
        }else{
            $folder_path = $this->getFolderPath($folder);
        }

        //Determine File Type (file / base64) and populate variables
        $file = null;
        $extension = null;
        $name_to_check = null;
        if($request->file('file') !== null) {
            $file = $request->file('file');
            $extension = $file->extension();
            $name_to_check = $request['filename'] . '.' . $extension;
        }else{
            $tmp_file = $this->convertB64ToFile($request['file_base64']);
            $file = $tmp_file['file'];
            $extension = $tmp_file['extension'];
            $name_to_check = $request['filename'] . '.' . $extension;
        }

        //Verify The Filename
        $filename = $this->verifyFilename($name_to_check, $folder_path);
        //Try to save file to disk
        try {
            $this->saveFileToDisk($folder_path, $file, $filename);
        }catch(\Exception $e) {
            Log::channel('unbound_file_log')->error('Image Upload Error: '. $e->getMessage());
            throw new ImageWriteException();
        }

        //Overwrite & Create needed vars and send to repo for creation
        $data['folder_id'] = $folder !== null ? $folder->id : null;
        $data['file_path'] = $folder_path . $filename;
        $data['file_name'] = $filename;
        $data['extension'] = $extension;

        //Create File
        return $this->imageRepository->create($data);
    }

    public function update(Request $request) {
        $data = $request->all();

        //Validate the request
        try {
            $this->imageRepository->validateUpdate($request);
        }catch(\Exception $e) {
            return $e->getMessage();
        }
        try {
            $image = $this->imageRepository->findByUUID($request['file_id']);
        }catch(\Exception $e) {
            return $e->getMessage();
        }

        return $this->imageRepository->update($data);
    }

    public function change(Request $request) {
        $data = $request->all();
        //Validate the request
        try {
            $this->imageRepository->validateChange($request);
        }catch(\Exception $e) {
            return $e->getMessage();
        }
        try {
            $image = $this->imageRepository->findByUUID($request['file_id']);
        }catch(\Exception $e) {
            dd($e->getMessage());
        }
        $new_filename = '';
        $current_file_name = $image->file_name;
        //Get Current Folder
        try {
            $folder = $this->folderRepository->findById($image->folder_id);
        }catch(\Exception $e) {
            return $e->getMessage();
        }
        //Build Folder Path
        if($folder === null) {
            $folder_path = 'public/';
        }else{
            $folder_path = $this->getFolderPath($folder);
        }
        //Get File
        if($request->file('file') !== null) {
            //Normal File
            $file = $request->file('file');
            $extension = $file->extension();
        }else{
            //Base 64 File
            $tmp_file = $this->convertB64ToFile($request['file_base64']);
            $extension = $tmp_file['extension'];
            $file = $tmp_file['file'];
        }

        //Important - we want to MOVE the old file first.  In the event something goes awry, we restore old
        $this->moveFileToTemp($folder_path, $image, 'image');
        //Check file name to prevent overwrite of other files
        if(isset($request['filename'])) {
            $new_filename = $request['filename'] . '.' .$extension;
            $filename = $this->verifyFilename($new_filename, $folder_path);
        }else{
            $new_filename = $current_file_name;
        }

        //Save new file to disk or restore old on failure
        try {
            $this->saveFileToDisk($folder_path, $file, $new_filename);
        }catch(\Exception $e) {
            $this->restoreFromTemp($folder_path, $image, 'image');
            Log::channel('unbound_file_log')->error('Image Upload Error during change: '. $e->getMessage());
            throw new ImageWriteException();
        }

        $data['extension'] = $extension;
        $data['folder_path'] = $folder_path;
        $data['filename'] = $new_filename;
        isset($request['title']) ? $data['title'] = $request['title'] : $image->title;
        isset($request['width']) ? $data['width'] = $request['width'] : $image->width;
        isset($request['height']) ? $data['height'] = $request['height'] : $image->height;
        isset($request['meta_data']) ? $data['meta_data'] = $request['meta_data'] : $image->meta_data;

        try {
            $this->imageRepository->change($data);
        }catch(\Exception $e) {
            //Failed to update db record
            $this->restoreFromTemp($folder_path, $image, 'image');
            Log::channel('unbound_file_log')->error('Image Database Error during change: '. $e->getMessage());
            throw new ImageDatabaseException();
        }

        //Remove Temp File
        $this->removeFromTemp($current_file_name, 'image');

        return Response::HTTP_OK;
    }

    public function move(Request $request) {
        $data = $request->all();
        //Validate the request
        try {
            $this->imageRepository->validateMoveCopy($request);
        }catch(\Exception $e) {
            dd($e->getMessage());
        }
        try {
            $image = $this->imageRepository->findByUUID($request['file_id']);
        }catch(\Exception $e) {
            dd($e->getMessage());
        }
        try {
            $folder = $this->folderRepository->findById($request['folder_id']);
        }catch(\Exception $e) {
            dd($e->getMessage());
        }

        $new_folder_path = $this->getFolderPath($folder);
        $filename = $this->verifyFilename($image->file_name, $new_folder_path);
        $newStorage = $new_folder_path . $filename;

        try {
            $this->moveFile($image->file_path, $newStorage);
        }catch(\Exception $e) {
            dd($e->getMessage());
        }

        $data['folder_id'] = $folder->id;
        $data['file_path'] = $newStorage;
        $data['filename'] = $filename;

        return $this->imageRepository->move($data);
    }
    public function copy(Request $request) {
        $data = $request->all();
        //Validate the request
        try {
            $this->imageRepository->validateMoveCopy($request);
        }catch(\Exception $e) {
            dd($e->getMessage());
        }
        //Get image
        try {
            $image = $this->imageRepository->findByUUID($request['file_id']);
        }catch(\Exception $e) {
            dd($e->getMessage());
        }
        //Get folder
        try {
            $folder = $this->folderRepository->findById($request['folder_id']);
        }catch(\Exception $e) {
            dd($e->getMessage());
        }
        $new_file_path = $this->getFolderPath($folder);
        $filename = $this->verifyFilename($image->file_name, $new_file_path);
        $fullpath = $new_file_path . $filename;

        //Copy file to new location
        try {
            $this->copyFileToFolder($fullpath, $image->file_path);
        }catch(\Exception $e) {
            dd($e->getMessage());
        }

        //Copy data from current image for the new database record
        $data['folder_id'] = $folder->id;
        $data['file_path'] = $fullpath;
        $data['file_name'] = $filename;
        $data['extension'] = $image->extension;
        $data['title'] = $image->title;
        $data['width'] = $image->width;
        $data['height'] = $image->height;
        $data['meta_data'] = $image->meta_data;

        //Create new file in database
        return $this->imageRepository->copy($data);
    }
    public function remove(Request $request) {
        $data = $request->all();
        //Validate the request
        try {
            $this->imageRepository->validateRemove($request);
        }catch(\Exception $e) {
            dd($e->getMessage());
        }
        //Get Image
        try {
            $image = $this->imageRepository->findByUUID($request['file_id']);
        }catch(\Exception $e) {
            dd($e->getMessage());
        }

        return $this->imageRepository->remove($data);
    }
}