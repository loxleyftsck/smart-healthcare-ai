<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Patient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class PatientApiTest extends TestCase
{
    use RefreshDatabase;

    protected function authenticate(): array
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);
        return ['Authorization' => "Bearer $token"];
    }

    public function test_can_get_patients_list()
    {
        $headers = $this->authenticate();
        Patient::factory()->count(3)->create();

        $response = $this->withHeaders($headers)->getJson('/api/patients');

        $response->assertStatus(200)
                 ->assertJsonStructure(['status', 'data']);
    }

    public function test_can_create_patient()
    {
        $headers = $this->authenticate();
        $payload = [
            'name' => 'John Doe',
            'email' => 'johndoe@example.com',
            'phone' => '1234567890',
            'gender' => 'male',
        ];

        $response = $this->withHeaders($headers)->postJson('/api/patients', $payload);

        $response->assertStatus(201)
                 ->assertJsonFragment(['name' => 'John Doe']);
                 
        $this->assertDatabaseHas('patients', ['email' => 'johndoe@example.com']);
    }

    public function test_unauthorized_user_cannot_access_patients()
    {
        $response = $this->getJson('/api/patients');
        $response->assertStatus(401);
    }
}
