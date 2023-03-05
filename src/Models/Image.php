<?php

namespace Devilwacause\UnboundCore\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Image extends Model
{
    use HasUuids;
    protected $table = 'unbound_images';

    protected $fillable = [
        'folder_id',
        'file_path',
        'file_name',
        'meta_data',
    ];

}