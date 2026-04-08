<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations for database optimization (PATH B Day 2)
     *
     * Creates strategic indexes for:
     * - Patient lookups by email (login optimization)
     * - Consultation queries by patient (history pages)
     * - Triage queries by severity (urgent case filtering)
     * - Composite indexes for complex queries
     */
    public function up(): void
    {
        // Optimize patients table
        Schema::table('patients', function (Blueprint $table) {
            // Email is already UNIQUE from original migration
            // Adding if not exists for safety
            if (!Schema::hasColumn('patients', 'created_at')) {
                $table->index('created_at')->comment('For sorting patient lists by date');
            }
            
            // Index on where queries
            if (!Schema::hasIndex('patients', 'idx_patients_created_at')) {
                $table->index('created_at', 'idx_patients_created_at')->comment('Patient listing/filtering');
            }
        });
        
        // Optimize consultations table
        Schema::table('consultations', function (Blueprint $table) {
            // Single column indexes
            if (!Schema::hasIndex('consultations', 'idx_consultations_patient_id')) {
                $table->index('patient_id', 'idx_consultations_patient_id')
                    ->comment('Patient consultation history queries');
            }
            
            if (!Schema::hasIndex('consultations', 'idx_consultations_session_id')) {
                $table->index('session_id', 'idx_consultations_session_id')
                    ->comment('Session lookup by ID');
            }
            
            if (!Schema::hasIndex('consultations', 'idx_consultations_created_at')) {
                $table->index('created_at', 'idx_consultations_created_at')
                    ->comment('Consultation sorting/filtering by date');
            }
            
            // Composite index: patient_id + created_at (most common query pattern)
            if (!Schema::hasIndex('consultations', 'idx_consultations_patient_created')) {
                $table->index(['patient_id', 'created_at'], 'idx_consultations_patient_created')
                    ->comment('Patient consultation history with date filtering/sorting');
            }
        });
        
        // Optimize triage_logs table
        Schema::table('triage_logs', function (Blueprint $table) {
            if (!Schema::hasIndex('triage_logs', 'idx_triage_logs_patient_id')) {
                $table->index('patient_id', 'idx_triage_logs_patient_id')
                    ->comment('Patient triage history');
            }
            
            if (!Schema::hasIndex('triage_logs', 'idx_triage_logs_consultation_id')) {
                $table->index('consultation_id', 'idx_triage_logs_consultation_id')
                    ->comment('Lookup triage by consultation');
            }
            
            if (!Schema::hasIndex('triage_logs', 'idx_triage_logs_severity')) {
                $table->index('severity', 'idx_triage_logs_severity')
                    ->comment('Filter HIGH severity cases for urgent handling');
            }
            
            // Composite: patient_id + severity (urgent cases for a patient)
            if (!Schema::hasIndex('triage_logs', 'idx_triage_logs_patient_severity')) {
                $table->index(['patient_id', 'severity'], 'idx_triage_logs_patient_severity')
                    ->comment('Find high-severity cases for specific patient');
            }
        });
    }
    
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('triage_logs', function (Blueprint $table) {
            $table->dropIndexIfExists('idx_triage_logs_patient_severity');
            $table->dropIndexIfExists('idx_triage_logs_severity');
            $table->dropIndexIfExists('idx_triage_logs_consultation_id');
            $table->dropIndexIfExists('idx_triage_logs_patient_id');
        });
        
        Schema::table('consultations', function (Blueprint $table) {
            $table->dropIndexIfExists('idx_consultations_patient_created');
            $table->dropIndexIfExists('idx_consultations_created_at');
            $table->dropIndexIfExists('idx_consultations_session_id');
            $table->dropIndexIfExists('idx_consultations_patient_id');
        });
        
        Schema::table('patients', function (Blueprint $table) {
            $table->dropIndexIfExists('idx_patients_created_at');
        });
    }
};
