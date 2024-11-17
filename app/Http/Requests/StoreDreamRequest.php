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
            'user_id' => ['required', 'exists:App\Models\User,id'],
            'title' => ['nullable', 'string'],
            'dream_content' => ['nullable', 'string'],

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
