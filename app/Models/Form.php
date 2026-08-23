<?php

namespace App\Models;

use App\Models\Concerns\HasTranslatableContent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Form extends Model
{
    use HasFactory, HasTranslatableContent;

    protected $attributes = [
        'translation_status' => 'pending',
    ];

    protected $fillable = [
        'title_ar',
        'title_en',
        'description_ar',
        'description_en',
        'slug',
        'is_active',
        'translation_status',
    ];

    public function fields()
    {
        return $this->hasMany(FormField::class);
    }

    public function submissions()
    {
        return $this->hasMany(FormSubmission::class);
    }

    protected function translatableFields(): array
    {
        return ['title', 'description'];
    }
}
