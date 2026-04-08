<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreConsultationRequest;
use App\Http\Resources\ConsultationResource;
use App\Services\ConsultationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(name="Consultations", description="AI-powered chat consultation endpoints")
 */
class ConsultationController extends Controller
{
    protected ConsultationService $consultationService;

    public function __construct(ConsultationService $consultationService)
    {
        $this->consultationService = $consultationService;
    }

    /**
     * @OA\Get(
     *     path="/api/consultations",
     *     tags={"Consultations"},
     *     summary="Get all consultations (paginated)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $consultations = $this->consultationService->getAll($request->integer('per_page', 15));
        return response()->json([
            'status' => 'success',
            'data' => ConsultationResource::collection($consultations)->response()->getData(true),
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/consultations",
     *     tags={"Consultations"},
     *     summary="Send a consultation message (triggers AI triage)",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"message"},
     *             @OA\Property(property="message", type="string", example="I have a fever and sore throat"),
     *             @OA\Property(property="patient_id", type="integer", example=1),
     *             @OA\Property(property="session_id", type="string", example="uuid-session-id")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Consultation created with AI response"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(StoreConsultationRequest $request): JsonResponse
    {
        $consultation = $this->consultationService->createWithAiTriage(
            $request->validated()
        );

        return response()->json([
            'status'  => 'success',
            'message' => 'Consultation processed successfully',
            'data'    => new ConsultationResource($consultation),
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/consultations/{id}",
     *     tags={"Consultations"},
     *     summary="Get a specific consultation",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function show(int $id): JsonResponse
    {
        $consultation = $this->consultationService->findOrFail($id);
        return response()->json([
            'status' => 'success',
            'data'   => new ConsultationResource($consultation),
        ]);
    }
}
