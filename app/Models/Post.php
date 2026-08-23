<?php

namespace App\Models;

use App\Models\Concerns\HasTranslatableContent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use HasFactory, HasTranslatableContent;

    protected $attributes = [
        'translation_status' => 'pending',
    ];

    protected $fillable = [
        'author_id',
        'title_ar',
        'title_en',
        'content_ar',
        'content_en',
        'slug',
        'cover_image',
        'status',
        'published_at',
        'translation_status',
    ];

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function isPublished()
    {
        return $this->status === 'published';
    }

    protected function translatableFields(): array
    {
        return ['title', 'content'];
    }
}
