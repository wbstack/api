<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class EventPageUpdate extends Model
{

    protected $fillable = [
        'site',
        'title',
        'namespace',
    ];

}