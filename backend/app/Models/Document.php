<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    protected $fillable = [
        'user_id', 'folder_id', 'name', 'path',
        'storage_type', 'size', 'mime_type', 'uploaded_at'
    ];

    protected $casts = [
        'uploaded_at' => 'datetime',
        'size' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function folder()
    {
        return $this->belongsTo(Folder::class);
    }
}
