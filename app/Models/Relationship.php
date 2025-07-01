<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Relationship extends Model
{
    protected $table = "relationships";
    public $timestamps = true;

    protected $fillable = [
        'followee_id',
        'follower_id'
    ];

}
