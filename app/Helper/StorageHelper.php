<?php

namespace App\Helper;

use Illuminate\Support\Facades\Storage;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Config;

class StorageHelper {

    public function getPublicStatic() : Filesystem {
        return Storage::disk(
            Config::get('wbstack.wiki_public_static_storage')
        );
    }
 
}