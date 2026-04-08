<?php

namespace App\Services;

use App\Models\Patient;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * PatientService - Business logic for patient management
 * PATH B: Integrated with QueryCacheService for performance
 */
class PatientService
{
    protected QueryCacheService $cacheService;
    
    /**
     * Constructor with dependency injection
     */
    public function __construct(QueryCacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    /**
     * Get all patients paginated (with caching)
     *
     * @param int $perPage
     * @param int $page
     * @return LengthAwarePaginator
     */
    public function getAll(int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        return $this->cacheService->rememberPatients(
            $page,
            $perPage,
            fn() => Patient::latest()->paginate($perPage, ['*'], 'page', $page),
            QueryCacheService::TTL_MEDIUM
        );
    }

    /**
     * Find patient by ID (with caching)
     *
     * @param int $id
     * @return Patient
     */
    public function findOrFail(int $id): Patient
    {
        return $this->cacheService->rememberPatient(
            $id,
            fn() => Patient::findOrFail($id),
            QueryCacheService::TTL_LONG
        );
    }

    /**
     * Create fresh patient (invalidates list cache)
     *
     * @param array $data
     * @return Patient
     */
    public function create(array $data): Patient
    {
        // Create the patient
        $patient = Patient::create($data);
        
        // Invalidate patient list cache
        $this->cacheService->invalidatePatients();
        
        return $patient;
    }

    /**
     * Update existing patient (invalidates caches)
     *
     * @param Patient $patient
     * @param array $data
     * @return Patient
     */
    public function update(Patient $patient, array $data): Patient
    {
        // Update the patient
        $patient->update($data);
        
        // Invalidate both individual and list caches
        $this->cacheService->invalidatePatient($patient->id);
        $this->cacheService->invalidatePatients();
        
        return $patient;
    }

    /**
     * Delete existing patient (invalidates caches)
     *
     * @param Patient $patient
     * @return bool
     */
    public function delete(Patient $patient): bool
    {
        $patientId = $patient->id;
        
        // Delete the patient
        $deleted = $patient->delete();
        
        if ($deleted) {
            // Invalidate caches
            $this->cacheService->invalidatePatient($patientId);
            $this->cacheService->invalidatePatients();
            $this->cacheService->invalidatePatientConsultations($patientId);
        }
        
        return $deleted;
    }
}
