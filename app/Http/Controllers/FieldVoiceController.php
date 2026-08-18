<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFieldVoiceRequest;
use App\Http\Requests\UpdateFieldVoiceRequest;
use App\Http\Resources\FieldVoiceResource;
use App\Models\FieldVoice;
use Illuminate\Http\Request;

class FieldVoiceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $fieldVoices = FieldVoice::where('is_published', true)->orderBy('created_at', 'desc')->get();

        return FieldVoiceResource::collection($fieldVoices);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreFieldVoiceRequest $request)
    {
        $validatedData = $request->validated();

        if ($request->hasFile('image')) {
            $validatedData['image'] = $request->file('image')
                ->store('field-voices', 'public');
        }

        $fieldVoice = FieldVoice::create($validatedData);

        return new FieldVoiceResource($fieldVoice);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateFieldVoiceRequest $request, FieldVoice $fieldVoice)
    {
        $validatedData = $request->validated();

        if ($request->hasFile('image')) {
            $validatedData['image'] = $request->file('image')
                ->store('field-voices', 'public');
        }

        $fieldVoice->update($validatedData);

        return new FieldVoiceResource($fieldVoice);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(FieldVoice $fieldVoice)
    {
        $fieldVoice->delete();

        return response()->noContent();
    }
}
