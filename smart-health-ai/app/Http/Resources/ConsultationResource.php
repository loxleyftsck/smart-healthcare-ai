<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConsultationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'session_id' => $this->session_id,
            'patient_id' => $this->patient_id,
            'message'    => $this->message,
            'intent'     => $this->intent,
            'response'   => $this->response,
            'triage'     => $this->whenLoaded('triageLogs', function () {
                return $this->triageLogs->first() ? [
                    'severity'       => $this->triageLogs->first()->severity,
                    'confidence'     => $this->triageLogs->first()->confidence,
                    'symptoms'       => $this->triageLogs->first()->symptoms,
                    'recommendation' => $this->triageLogs->first()->recommendation,
                ] : null;
            }),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
