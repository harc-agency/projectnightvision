<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDreamRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // only authenticated users can create dreams
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'dream_location' => ['nullable', 'string', 'max:255'],
            'dream_content' => ['required', 'string'],
            'dream_audio' => ['nullable', 'file', 'mimes:mp3,wav,ogg,m4a,webm,mp4', 'max:25600'],
            'is_public' => ['nullable', 'boolean'],
            'save_location_to_profile' => ['nullable', 'boolean'],
            'location' => ['nullable', 'array'],
            'location.lat' => ['required_with:location', 'numeric', 'between:-90,90'],
            'location.lng' => ['required_with:location', 'numeric', 'between:-180,180'],
            'location.label' => ['nullable', 'string', 'max:255'],
            'location.accuracy' => ['nullable', 'numeric', 'min:0'],
            'location.source' => ['nullable', 'string', 'max:64'],
            'location.captured_at' => ['nullable', 'date'],

            // 'dream_audio' => ['nullable', 'file', 'mimes:mp3', 'max:10240'],
            // 'dream_date' => ['required', 'datetime'],
            // 'mood_before_sleep' => ['nullable', 'string'],
            // 'mood_after_waking' => ['nullable', 'string'],
            // 'intensity' => ['nullable', 'integer', 'min:1', 'max:5'],
            // 'sleep_quality' => ['nullable', 'integer', 'min:1', 'max:5'],
            // 'overall_theme' => ['nullable', 'string'],
            // 'analysis' => ['nullable', 'string'],
            // 'sentiment' => ['nullable', 'string'],
            // 'sleep_duration_hours' => ['nullable', 'integer'],
            // 'location' => ['nullable', 'string']
        ];

    }
}
