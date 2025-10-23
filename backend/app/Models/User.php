<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;

    protected $fillable = ['name', 'email', 'password', 'role'];
    protected $hidden = ['password', 'remember_token'];

    public function folders()
    {
        return $this->hasMany(Folder::class);
    }

    public function documents()
    {
        return $this->hasMany(Document::class);
    }

    public function isAdmin()
    {
        return $this->role === 'admin';
    }
}
