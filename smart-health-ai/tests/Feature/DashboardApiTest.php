<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Patient;
use App\Models\Consultation;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class DashboardApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Tenant $tenant;
    protected array $headers;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a tenant and a user belonging to it
        $this->tenant = Tenant::factory()->create([
            'name'   => 'Test Clinic',
            'domain' => 'testclinic.smarthealth.ai',
        ]);

        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $token = JWTAuth::fromUser($this->user);
        $this->headers = ['Authorization' => "Bearer $token"];
    }

    public function test_dashboard_requires_authentication(): void
    {
        $response = $this->getJson('/api/dashboard');
        $response->assertStatus(401);
    }

    public function test_dashboard_returns_expected_structure(): void
    {
        $response = $this->withHeaders($this->headers)->getJson('/api/dashboard');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'status',
                     'data' => [
                         'user' => ['name', 'email', 'tenant_id'],
                         'my_patients',
                         'recent_consultations',
                         'metrics' => ['total_consultations', 'recent_high_severity'],
                     ],
                 ]);
    }

    public function test_dashboard_returns_zero_metrics_with_no_data(): void
    {
        $response = $this->withHeaders($this->headers)->getJson('/api/dashboard');

        $response->assertStatus(200)
                 ->assertJsonPath('data.metrics.total_consultations', 0)
                 ->assertJsonPath('data.metrics.recent_high_severity', 0)
                 ->assertJsonPath('status', 'success');
    }

    public function test_dashboard_reflects_correct_user_info(): void
    {
        $response = $this->withHeaders($this->headers)->getJson('/api/dashboard');

        $response->assertStatus(200)
                 ->assertJsonPath('data.user.email', $this->user->email)
                 ->assertJsonPath('data.user.tenant_id', $this->tenant->id);
    }

    public function test_dashboard_counts_consultations_correctly(): void
    {
        // Create a patient linked to this user's email
        $patient = Patient::factory()->create([
            'email'     => $this->user->email,
            'tenant_id' => $this->tenant->id,
        ]);

        // Create 3 consultations
        Consultation::factory()->count(3)->create([
            'patient_id' => $patient->id,
            'tenant_id'  => $this->tenant->id,
        ]);

        $response = $this->withHeaders($this->headers)->getJson('/api/dashboard');

        $response->assertStatus(200)
                 ->assertJsonPath('data.metrics.total_consultations', 3);
    }
}
