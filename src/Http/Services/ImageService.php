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
     * ImageRepository constructor.
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


}