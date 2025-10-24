<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Folder extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'parent_id',
        'user_id',
        's3_path',
    ];

    public function parent()
    {
        return $this->belongsTo(Folder::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Folder::class, 'parent_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Automatically delete from S3 when folder deleted
    protected static function booted()
    {
        static::deleting(function ($folder) {
            if ($folder->s3_path && Storage::disk('s3')->exists($folder->s3_path)) {
                Storage::disk('s3')->deleteDirectory($folder->s3_path);
            }
        });
    }
}
