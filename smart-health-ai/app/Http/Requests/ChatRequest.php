<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Chat Request
 * 
 * Validates incoming chat messages to Mistral 7B healthcare assistant.
 */
class ChatRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'min:3', 'max:2000'],
            'session_id' => ['nullable', 'string', 'max:36'],
            'patient_id' => ['nullable', 'integer', 'exists:patients,id'],
            'include_history' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Get custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            'message.required' => 'Pesan tidak boleh kosong',
            'message.min' => 'Pesan minimal 3 karakter',
            'message.max' => 'Pesan maksimal 2000 karakter',
            'session_id.max' => 'Session ID invalid',
            'patient_id.exists' => 'Patient tidak ditemukan',
        ];
    }
}
