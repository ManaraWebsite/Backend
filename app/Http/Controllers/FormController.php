<?php

namespace App\Http\Controllers;

use App\Http\Requests\SubmitFormRequest;
use App\Http\Resources\FormResource;
use App\Models\Form;
use App\Models\SubmissionAnswer;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class FormController extends Controller
{
    /**
     * Display the specified resource.
     */
    public function show(Form $form)
    {
        abort_unless($form->is_active, 404);

        $form->load(['fields' => fn($q) => $q->orderBy('order')]);

        return new FormResource($form);
    }

    public function submit(SubmitFormRequest $request, Form $form)
    {
        // Uploads from `file` fields are swapped for their stored path. They're
        // written before the transaction because a rollback cannot delete a
        // file, and to the private disk because they're applicants' documents:
        // admins reach them only through expiring signed links.
        $answers = collect($request->validated('answers', []))
            ->map(fn ($answer) => $answer instanceof UploadedFile
                ? $answer->store("submissions/{$form->id}", 'local')
                : $answer);

        $submission = DB::transaction(function () use ($answers, $form) {
            $submission = $form->submissions()->create([
                'submitted_at' => now(),
            ]);

            foreach ($answers as $fieldId => $answer) {
                SubmissionAnswer::create([
                    'submission_id' => $submission->id,
                    'field_id' => $fieldId,
                    'answer' => is_array($answer) ? json_encode($answer) : $answer,
                ]);
            }

            return $submission;
        });

        return response()->json([
            'message' => 'Registration submitted successfully.',
            'submission_id' => $submission->id,
        ], 201);
    }
}
