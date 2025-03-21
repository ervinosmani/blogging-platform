<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'content',
        'category',
        'user_id',
        'likes',
        'status',
    ];

    // Kjo metode krijon nje lidhje me modelin User per te marre autorin e postimit
    public function user() 
    {
        return $this->belongsTo(User::class);
    }
}
