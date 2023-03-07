<?php

namespace Devilwacause\UnboundCore\Http\Controllers;

use Devilwacause\UnboundCore\Exceptions\DatabaseExceptions\DatabaseException;
use Devilwacause\UnboundCore\Exceptions\ImageExceptions\ImageNotFoundException;
use Devilwacause\UnboundCore\Models\Image;
use Devilwacause\UnboundCore\Models\Folder;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Storage;
use League\Glide\ServerFactory;
use League\Glide\Server;

class ImageController extends BaseController
{
    private \League\Glide\Server $glide;

    public function __construct() {
        $this->glide = ServerFactory::create([
            'source' => config('glide.SOURCE'),
            'cache' => config('glide.CACHE'),
            'driver' => config('glide.DRIVER'),
            'presets' => config('glide.PRESETS')
        ]);
    }

    /**
     * @param $fileUUID
     * @return void
     * @throws ImageNotFoundException
     */
    public function show($fileUUID) {
        try {
            $image = Image::where('id', $fileUUID)->first();
        }catch(\Illuminate\Database\QueryException $e) {
            throw new DatabaseException($e->getMessage());
        }
        if($image === null) {
            throw new ImageNotFoundException();
        }else{
            $this->glide->getImageResponse($image->file_path, request()->all());
        }
    }

    public function create(Request $request) {
        $request->validate([
            'file' => 'required|image',
            'filename' => 'required|string',
            'folder' => 'string|min:3',
            'width' => 'integer',
            'height' => 'integer',
            'title' => 'string',
            'meta_data' => 'json'
        ]);
    }

}