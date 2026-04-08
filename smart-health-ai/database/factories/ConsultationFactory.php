<?php

namespace Database\Factories;

use App\Models\Patient;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Consultation>
 */
class ConsultationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'session_id' => Str::uuid(),
            'message' => fake()->sentence(),
            'intent' => fake()->randomElement(['symptom_query', 'general_inquiry', 'schedule']),
            'response' => fake()->paragraph(),
            'tenant_id' => Tenant::factory(),
        ];
    }
}
