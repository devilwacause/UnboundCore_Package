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
        $default_thumbnail = config('unbound.DEFAULT_THUMBNAIL');
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
        $image->original = '/img/' .$image->id;
    }
}