<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Patient;
use App\Models\Consultation;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Get the dashboard overview for the authenticated user/patient.
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        // Find patient profiles associated with this user
        // Assuming a user can view their patients (or if user is a clinic admin, view their tenant patients)
        // For standard patient view:
        $patients = Patient::where('email', $user->email)
            ->orWhere('tenant_id', $user->tenant_id)
            ->with(['consultations' => function($query) {
                $query->latest()->take(5)->with('triageLogs');
            }])
            ->get();

        $recentConsultations = Consultation::whereIn('patient_id', $patients->pluck('id'))
            ->with('triageLogs')
            ->latest()
            ->take(10)
            ->get();

        // Calculate some basic trend metrics
        $totalConsultations = Consultation::whereIn('patient_id', $patients->pluck('id'))->count();
        $highSeverityCount = $recentConsultations->filter(function($c) {
            return $c->triageLogs->contains('severity', 'HIGH');
        })->count();

        return response()->json([
            'status' => 'success',
            'data' => [
                'user' => [
                    'name' => $user->name,
                    'email' => $user->email,
                    'tenant_id' => $user->tenant_id
                ],
                'my_patients' => $patients->map(function($p) {
                    return [
                        'id' => $p->id,
                        'name' => $p->name,
                        'total_consultations' => $p->consultations->count(),
                        'recent_risk' => $p->consultations->first()?->triageLogs->first()?->severity ?? 'UNKNOWN'
                    ];
                }),
                'recent_consultations' => $recentConsultations,
                'metrics' => [
                    'total_consultations' => $totalConsultations,
                    'recent_high_severity' => $highSeverityCount,
                ]
            ]
        ]);
    }
}
