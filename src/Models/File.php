<?php

namespace Devilwacause\UnboundCore\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class File extends Model
{
    use HasUuids;
    protected $table = 'unbound_files';

    protected $fillable = [
        'folder_id',
        'file_path',
        'file_name',
        'extension',
        'width',
        'height',
        'title',
        'meta_data',
    ];

}