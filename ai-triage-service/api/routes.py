from fastapi import APIRouter, HTTPException
from models.schemas import TriageRequest, TriageResponse
from services.triage_service import analyze_symptoms
import logging

router = APIRouter()
logger = logging.getLogger("ai_triage")

@router.post("/triage", response_model=TriageResponse, status_code=200)
async def perform_triage(request: TriageRequest):
    """
    Perform AI triage analysis on user inputted message.
    """
    try:
        triage_data = analyze_symptoms(request.message)
        return TriageResponse(**triage_data)
    except ValueError as ve:
        logger.error(f"Triage validation error: {ve}")
        raise HTTPException(status_code=500, detail=str(ve))
    except Exception as e:
        logger.error(f"Server error during triage: {e}")
        raise HTTPException(status_code=500, detail="Internal AI server error")
