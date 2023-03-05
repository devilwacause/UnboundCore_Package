<?php

namespace Devilwacause\UnboundCore\Models;

use Illuminate\Database\Eloquent\Model;
class Folder extends Model
{
    protected $table = 'unbound_folders';

    protected $fillable = [
      'parent_id',
      'folder_name'
    ];
}