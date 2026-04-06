<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreConsultationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'message'    => 'required|string|min:5|max:2000',
            'patient_id' => 'nullable|integer|exists:patients,id',
            'session_id' => 'nullable|uuid',
        ];
    }
}
