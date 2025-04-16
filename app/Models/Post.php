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
        'category_id',
        'user_id',
        'likes',
        'status',
        'published_at',
        'image'
    ];

    /**
     * Marrim autorin e postimit.
     */
    public function user() 
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Marrim kategorine perkatese.
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Marrim komentet e postit.
     */
    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * Marrim likes per postin.
     */
    public function likes()
    {
        return $this->hasMany(PostLike::class);
    }

    /**
     * Krijon automatikisht slug bazuar ne titull.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($post) {
            if (empty($post->slug)) {
                $post->slug = Str::slug($post->title);
            }
        });
    }
}
