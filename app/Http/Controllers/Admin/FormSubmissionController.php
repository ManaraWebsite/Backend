<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\FormSubmissionResource;
use App\Models\Form;

class FormSubmissionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Form $form)
    {
        $submissions = $form->submissions()
            ->with('answers')
            ->get();

        return response()->json([
            'form' => $form->count(),
            'submissions' => FormSubmissionResource::collection($submissions),
        ]);
    }

    /**
     * Export the form submissions to a CSV file.
     */
    public function export(Form $form)
    {
        $form->load(['fields' => fn ($q) => $q->orderBy('order')]);
        $submissions = $form->submissions()->with('answers')->orderBy('submitted_at')->get();

        $filename = $form->slug.'-submissions-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($form, $submissions) {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM so Excel renders Arabic/non-Latin text correctly
            fwrite($handle, "\xEF\xBB\xBF");

            // Header row: Submitted at + one column per form field, in order
            $headers = ['Submitted at', ...$form->fields->pluck('label_ar')];
            fputcsv($handle, $headers);

            foreach ($submissions as $submission) {
                $answersByField = $submission->answers->keyBy('field_id');

                $row = [$submission->submitted_at];
                foreach ($form->fields as $field) {
                    $answer = $answersByField->get($field->id);

                    // A CSV is opened long after it's downloaded, so file
                    // links get a longer window than the API's one hour.
                    $row[] = $field->type === 'file'
                        ? $answer?->fileUrl(now()->addDays(7)) ?? ''
                        : $answer?->answer ?? '';
                }

                fputcsv($handle, $row);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
}
