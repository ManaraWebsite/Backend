<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FormSubmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'form_id',
        'submitted_at',
    ];

    public function form()
    {
        return $this->belongsTo(Form::class);
    }

    public function answers()
    {
        return $this->hasMany(SubmissionAnswer::class, 'submission_id');
    }
}
