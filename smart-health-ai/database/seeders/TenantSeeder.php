<?php

namespace Database\Seeders;

use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TenantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Tenant::firstOrCreate(
            ['domain' => 'demo.smarthealth.ai'],
            ['name' => 'Demo Clinic Default Tenant', 'id' => 1]
        );
    }
}
