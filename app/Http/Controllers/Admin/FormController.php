<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFormRequest;
use App\Http\Requests\UpdateFormRequest;
use App\Http\Resources\FormResource;
use App\Models\Form;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FormController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return FormResource::Collection(Form::with(['fields', 'submissions'])->get());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreFormRequest $request)
    {
        $form = DB::transaction(function () use ($request) {
            $form = Form::create([
                'title_ar' => $request->title,
                'description_ar' => $request->description,
                'slug' => $this->generateUniqueSlug($request->title),
                'is_active' => $request->boolean('is_active', false),
            ]);

            foreach ($request->input('fields') as $index => $fieldData) {
                $form->fields()->create([
                    'label_ar' => $fieldData['label'],
                    'type' => $fieldData['type'],
                    'options' => $fieldData['options'] ?? null,
                    'is_required' => $fieldData['is_required'] ?? false,
                    'order' => $fieldData['order'] ?? $index + 1,
                ]);
            }

            return $form;
        });

        return (new FormResource($form->load('fields')))
            ->response()
            ->setStatusCode(201);
    }

    private function generateUniqueSlug(string $title): string
    {
        $slug = Str::slug($title);
        $original = $slug;
        $count = 1;

        while (Form::where('slug', $slug)->exists()) {
            $slug = "{$original}-{$count}";
            $count++;
        }

        return $slug;
    }

    /**
     * Display the specified resource.
     */
    public function show(Form $form)
    {
        return new FormResource($form->load('fields', 'submissions'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateFormRequest $request, Form $form)
    {
        DB::transaction(function () use ($request, $form) {
            $formData = $request->only(['title', 'description', 'slug', 'is_active']);

            if (array_key_exists('title', $formData)) {
                $formData['title_ar'] = $formData['title'];
                unset($formData['title']);
            }

            if (array_key_exists('description', $formData)) {
                $formData['description_ar'] = $formData['description'];
                unset($formData['description']);
            }

            $form->update($formData);

            if ($request->has('fields')) {
                $submittedIds = [];

                foreach ($request->input('fields') as $index => $fieldData) {
                    $field = $form->fields()->updateOrCreate(
                        ['id' => $fieldData['id'] ?? null],
                        [
                            'label_ar' => $fieldData['label'],
                            'type' => $fieldData['type'],
                            'options' => $fieldData['options'] ?? null,
                            'is_required' => $fieldData['is_required'] ?? false,
                            'order' => $fieldData['order'] ?? $index + 1,
                        ]
                    );

                    $submittedIds[] = $field->id;
                }

                // Delete fields that existed before but weren't included in this request
                $form->fields()->whereNotIn('id', $submittedIds)->delete();
            }
        });

        return new FormResource($form->fresh('fields'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Form $form)
    {
        $form->delete();

        return response()->json(null, 204);
    }

    public function duplicate(Form $form)
    {
        $form->load('fields');

        $newForm = DB::transaction(function () use ($form) {
            $newForm = Form::create([
                'title_ar' => $form->title_ar.' (Copy)',
                'description_ar' => $form->description_ar,
                'slug' => $this->generateUniqueSlug($form->title_ar),
                'is_active' => false,
            ]);

            foreach ($form->fields as $field) {
                $newForm->fields()->create([
                    'label_ar' => $field->label_ar,
                    'type' => $field->type,
                    'options' => $field->options,
                    'is_required' => $field->is_required,
                    'order' => $field->order,
                ]);
            }

            return $newForm;
        });

        return (new FormResource($newForm->load('fields')))
            ->response()
            ->setStatusCode(201);
    }
}
