<?php

namespace Devilwacause\UnboundCore\Traits;

class Filesystem
{
    private string $fileSystem;
    public function __construct() {
        $this->fileSystem = config('unbound.UNBOUND_FILESYSTEM');
    }

    public function getFreeDiskSpace() {
        if($this->fileSystem === "LOCAL" || $this->fileSystem === 'local') {
            return disk_free_space(public_path());
        }else{
            return 1000000000000;
        }
    }
}