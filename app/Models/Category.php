<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = ['name'];

    public static function defaultCategories(): array
    {
        return [
            'Photography',
            'Technology',
            'Lifestyle',
            'Design',
            'Education',
            'Opinions',
            'Productivity',
        ];
    }
}
