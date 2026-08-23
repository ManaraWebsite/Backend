<?php

namespace App\Models;

use App\Models\Concerns\HasTranslatableContent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FieldVoice extends Model
{
    use HasFactory, HasTranslatableContent;

    protected $attributes = [
        'translation_status' => 'pending',
    ];

    protected $fillable = [
        'name',
        'role_ar',
        'role_en',
        'quote_ar',
        'quote_en',
        'image',
        'is_published',
        'translation_status',
    ];

    protected function translatableFields(): array
    {
        return ['role', 'quote'];
    }
}
