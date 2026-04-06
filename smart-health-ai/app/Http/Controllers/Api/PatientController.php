<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePatientRequest;
use App\Http\Requests\UpdatePatientRequest;
use App\Http\Resources\PatientResource;
use App\Services\PatientService;
use Illuminate\Http\JsonResponse;

class PatientController extends Controller
{
    protected PatientService $patientService;

    public function __construct(PatientService $patientService)
    {
        $this->patientService = $patientService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $patients = $this->patientService->getAll();
        return response()->json([
            'status' => 'success',
            'data' => PatientResource::collection($patients)->response()->getData(true)
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePatientRequest $request): JsonResponse
    {
        $patient = $this->patientService->create($request->validated());
        
        return response()->json([
            'status' => 'success',
            'message' => 'Patient created successfully',
            'data' => new PatientResource($patient)
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(int $id): JsonResponse
    {
        $patient = $this->patientService->findOrFail($id);
        
        return response()->json([
            'status' => 'success',
            'data' => new PatientResource($patient)
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePatientRequest $request, int $id): JsonResponse
    {
        $patient = $this->patientService->findOrFail($id);
        $updatedPatient = $this->patientService->update($patient, $request->validated());
        
        return response()->json([
            'status' => 'success',
            'message' => 'Patient updated successfully',
            'data' => new PatientResource($updatedPatient)
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id): JsonResponse
    {
        $patient = $this->patientService->findOrFail($id);
        $this->patientService->delete($patient);
        
        return response()->json([
            'status' => 'success',
            'message' => 'Patient deleted successfully'
        ]);
    }
}
