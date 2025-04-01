<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Post extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'content',
        'category',
        'user_id',
        'likes',
        'status',
        'published_at',
        'image'
    ];

    // Kjo metode krijon nje lidhje me modelin User per te marre autorin e postimit
    public function user() 
    {
        return $this->belongsTo(User::class);
    }

    //Krijimi automatik i slug bazuar ne titull
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($post) {
            if(empty($post->slug)) {
                $post->slug = Str::slug($post->title); //Krijon slug nga titulli
            }
        });
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function likes()
    {
        return $this->hasMany(PostLike::class);
    }
}
