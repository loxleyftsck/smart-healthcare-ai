<?php

namespace App\Services;

use App\Models\Patient;
use Illuminate\Pagination\LengthAwarePaginator;

class PatientService
{
    /**
     * Get all patients paginated
     *
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getAll(int $perPage = 15): LengthAwarePaginator
    {
        return Patient::latest()->paginate($perPage);
    }

    /**
     * Find patient by ID
     *
     * @param int $id
     * @return Patient
     */
    public function findOrFail(int $id): Patient
    {
        return Patient::findOrFail($id);
    }

    /**
     * Create fresh patient
     *
     * @param array $data
     * @return Patient
     */
    public function create(array $data): Patient
    {
        return Patient::create($data);
    }

    /**
     * Update existing patient
     *
     * @param Patient $patient
     * @param array $data
     * @return Patient
     */
    public function update(Patient $patient, array $data): Patient
    {
        $patient->update($data);
        return $patient;
    }

    /**
     * Delete existing patient
     *
     * @param Patient $patient
     * @return bool
     */
    public function delete(Patient $patient): bool
    {
        return $patient->delete();
    }
}
