<?php

namespace Devilwacause\UnboundCore\Observers;

use Devilwacause\UnboundCore\Models\Image;

class ImageObserver
{
    public function updating(Image $image)
    {
        unset($image->thumbnail);
        unset($image->original);
    }
    public function retrieved(Image $image) {
        $default_thumbnail = config('glide.DEFAULT_THUMBNAIL');
        $thumbnailString = '?';
        $params = count($default_thumbnail);
        $string_counter = 0;
        foreach ($default_thumbnail as $key => $value) {
            $thumbnailString .= $key . '=' . $value;
            if ($string_counter < $params - 1) {
                $thumbnailString .= '&';
            }
        }
        $image->thumbnail = '/img/' . $image->id . $thumbnailString;
        $image_params = '?';
        if($image->width !== null) {
            $image_params .= "w={$image->width}";
        }
        if($image->height !== null) {
            $image_params .= "w={$image->height}";
        }
        if($image->extension !== null) {
            $image_params .= "fm={$image->extension}";
        }
        $image->original = '/img/' .$image->id . $image_params;
    }
}