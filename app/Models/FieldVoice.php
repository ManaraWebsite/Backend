<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FieldVoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'role',
        'quote',
        'image',
        'is_published',
    ];
}
