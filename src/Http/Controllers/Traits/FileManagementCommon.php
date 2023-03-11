<?php

namespace Devilwacause\UnboundCore\Http\Controllers\Traits;

use Devilwacause\UnboundCore\Exceptions\DatabaseExceptions\DatabaseException;
use Devilwacause\UnboundCore\Models as Model;
use Illuminate\Support\Facades\Storage;

trait FileManagementCommon
{
    private $filesystem;

    public function __construct() {
        $this->filesystem = $this->getFileSystem();
    }

    /**
     * Get folder path for file
     * @param $folder
     * @return string
     */
    public function getFolderPath($folder) {
        $path = '/';
        if($folder->parent_id !== null) {
            $foundRoot = false;
            $currentParent = $folder->parent_id;

            do{
                $parent = Model\Folder::where('id', $currentParent)->first();

                if($parent->parent_id !== null) {
                    $path = '/' .$parent->folder_name . $path;

                    $currentParent = $parent->parent_id;
                }else{
                    $path = '/' .$parent->folder_name . $path;
                    $foundRoot = true;
                }
            }while(!$foundRoot);
            return 'public' . $path . $folder->folder_name . '/';
        }else{
            return 'public/' .$folder->folder_name . '/';
        }
    }

    /**
     * Create Folder in Database
     * @param array $data
     * @return mixed
     */
    public function createFolder(array $data) {
        //Create new folder in database
        try {
            $folder = Model\Folder::create([
                'parent_id' => $data['parent_id'],
                'folder_name' => $data['folder_name']
            ]);
        }catch(\Exception $e) {
            throw new DatabaseException($e->getMessage());
        }
        return $folder;
    }

    public function verifyFilename($filename, $folder) {
        if(Storage::fileExists($folder . $filename)) {
            $filename_parts = explode('.', $filename);
            $drop_last_part = array_splice($filename_parts, 0, -1);
            $filename = implode('.', $drop_last_part);
            $filename = $filename . '_' . date('mdHis') . '.' .$filename_parts[count($filename_parts) - 1];
            return $filename;
        }else{
            return $filename;
        }
    }

    public function saveFileToDisk($folder, $file, $filename) {
        try {
            Storage::putFileAs($folder, $file, $filename);
        }catch(\Exception $e) {
            throw new \Exception();
        }
    }

    public function removeFileFromDisk($filepath, $isImage = false) {
        try {
            Storage::delete($filepath);
        }catch(\Exception $e) {
            throw new \Exception();
        }
        if($isImage) {
            try {
                $cache_folder = str_replace('public', 'image_cache', $filepath);
                Storage::deleteDirectory($cache_folder);
            }catch(\Exception $e) {
                throw new \Exception();
            }
        }
    }

    private function getFileSystem() {
        return '';
    }

}