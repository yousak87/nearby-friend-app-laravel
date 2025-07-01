<?php

namespace App\Models;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Model implements Authenticatable
{
    protected $table = "users";
    protected $primaryKey = "id";
    protected $keyType = "int";
    public $timestamps = true;
    public $incrementing = true;


    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'dob',
        'address',
        'description',
        'latitude',
        'longitude'
    ];

    public function relationships(): HasMany {
        return $this->hasMany(Relationship::class,'followee_id', 'id');
    }

    public function following()
    {
        return $this->belongsToMany(User::class, 'relationships', 'follower_id', 'followee_id');
    }

    public function followers()
    {
        return $this->belongsToMany(User::class, 'relationships', 'followee_id', 'follower_id');
    }

    public function getAuthIdentifierName()
    {
        return 'username';
    }

    public function getAuthIdentifier()
    {
        return $this->username;
    }

    public function getAuthPassword()
    {
        return $this->password;
    }

    public function getRememberToken()
    {
        return $this->token;
    }

    public function setRememberToken($value)
    {
        $this->token = $value;
    }

    public function getRememberTokenName()
    {
        return 'token';
    }
}
