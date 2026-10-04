<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class SubmissionAnswer extends Model
{
    use HasFactory;

    protected $table = 'submission-answers';

    protected $fillable = [
        'submission_id',
        'field_id',
        'answer',
    ];

    public function submission()
    {
        return $this->belongsTo(FormSubmission::class);
    }

    public function field()
    {
        return $this->belongsTo(FormField::class, 'field_id');
    }

    /**
     * A link to an uploaded file answer that stops working at `$expiresAt`.
     *
     * Answers to `file` fields hold a path on the private `local` disk, which
     * is only reachable through Laravel's signed `/storage/{path}` route.
     */
    public function fileUrl(DateTimeInterface $expiresAt): ?string
    {
        if (blank($this->answer)) {
            return null;
        }

        return Storage::disk('local')->temporaryUrl($this->answer, $expiresAt);
    }
}
