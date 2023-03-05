<?php

namespace Devilwacause\UnboundCore\Http\Controllers;

use Devilwacause\UnboundCore\Exceptions\ImageExceptions\ImageNotFoundException;
use Devilwacause\UnboundCore\Models\Image;
use Devilwacause\UnboundCore\Models\Folder;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use League\Glide\ServerFactory;

class ImageController
{
    private ServerFactor $glide;

    public function __construct() {
        $this->glide = ServerFactory::create([
            'source' => config('glide.SOURCE'),
            'cache' => config('glide.CACHE'),
            'driver' => config('glide.DRIVER'),
            'presets' => config('glide.PRESETS')
        ]);
    }

    public function show($fileUUID) {
        $image = Image::where('id', $fileUUID)->first();
        if($image === null) {
            throw new ImageNotFoundException();
        }else{
            $this->glide->getImageResponse($image->file_path, request()->all());
        }
    }

}