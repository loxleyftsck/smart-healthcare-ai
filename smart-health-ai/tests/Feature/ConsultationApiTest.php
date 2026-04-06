<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Patient;
use App\Models\Consultation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Http;

class ConsultationApiTest extends TestCase
{
    use RefreshDatabase;

    protected function authenticate(): array
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);
        return ['Authorization' => "Bearer $token"];
    }

    public function test_can_create_consultation_with_triage()
    {
        Http::fake([
            'localhost:8001/api/triage' => Http::response([
                'intent' => 'emergency_triage',
                'response' => 'Mock AI response',
                'symptoms' => ['fever', 'chest pain'],
                'severity' => 'HIGH',
                'confidence' => 0.99,
                'recommendation' => 'Mock recommendation'
            ], 200)
        ]);
        
        $headers = $this->authenticate();
        $patient = Patient::factory()->create();

        $payload = [
            'patient_id' => $patient->id,
            'message'    => 'I have a very high fever and chest pain',
        ];

        // This will hit the fallback triage since AI service is not running
        $response = $this->withHeaders($headers)->postJson('/api/consultations', $payload);

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'status',
                     'message',
                     'data' => [
                         'id',
                         'session_id',
                         'message',
                         'intent',
                         'response',
                         'triage' => [
                             'severity',
                             'confidence',
                             'symptoms',
                             'recommendation'
                         ]
                     ]
                 ]);

        // Assert database structure
        $this->assertDatabaseHas('consultations', [
            'patient_id' => $patient->id,
            'message'    => 'I have a very high fever and chest pain',
        ]);
        
        $this->assertDatabaseHas('triage_logs', [
            'patient_id' => $patient->id,
        ]);
    }

    public function test_can_get_consultations_list()
    {
        $headers = $this->authenticate();
        $patient = Patient::factory()->create();
        
        Consultation::create([
            'patient_id' => $patient->id,
            'session_id' => 'test-session-123',
            'message' => 'Test message',
            'intent' => 'general_inquiry',
            'response' => 'Test response',
        ]);

        $response = $this->withHeaders($headers)->getJson('/api/consultations');

        $response->assertStatus(200)
                 ->assertJsonStructure(['status', 'data']);
                 
        $this->assertCount(1, $response->json('data.data'));
    }
}
