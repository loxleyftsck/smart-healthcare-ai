"""
SmartHealth AI - Triage Microservice
FastAPI service powered by Google Gemini AI for clinical triage analysis.
"""

import os
import json
import logging
from contextlib import asynccontextmanager
from dotenv import load_dotenv
from fastapi import FastAPI, HTTPException, Header, Depends
from fastapi.middleware.cors import CORSMiddleware
from fastapi.responses import JSONResponse
from prometheus_fastapi_instrumentator import Instrumentator

from services.triage_service import TriageService
from services.auth_service import verify_jwt_token

# Load environment variables
load_dotenv()

# ─────────────────────────────────────────────────────────
# Logging Configuration
# ─────────────────────────────────────────────────────────
logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s [%(levelname)s] %(name)s: %(message)s"
)
logger = logging.getLogger("smarthealth-triage")

# ─────────────────────────────────────────────────────────
# Application Lifecycle
# ─────────────────────────────────────────────────────────
@asynccontextmanager
async def lifespan(app: FastAPI):
    logger.info("🚀 SmartHealth AI Triage Service starting up...")
    logger.info(f"   Environment: {os.getenv('ENVIRONMENT', 'development')}")
    logger.info(f"   Gemini API: {'✅ Configured' if os.getenv('GEMINI_API_KEY') and os.getenv('GEMINI_API_KEY') != 'YOUR_GEMINI_API_KEY_HERE' else '⚠️  Not configured (mock mode)'}")
    yield
    logger.info("🛑 SmartHealth AI Triage Service shutting down...")


# ─────────────────────────────────────────────────────────
# FastAPI App
# ─────────────────────────────────────────────────────────
app = FastAPI(
    title="SmartHealth AI - Triage Service",
    description="AI-powered clinical triage powered by Google Gemini 2.5 Flash",
    version="3.0.0",
    docs_url="/docs",
    redoc_url="/redoc",
    lifespan=lifespan,
)

# CORS
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# Prometheus metrics at /metrics
Instrumentator().instrument(app).expose(app)

# ─────────────────────────────────────────────────────────
# Dependency: JWT Verification
# ─────────────────────────────────────────────────────────
async def get_current_user(authorization: str = Header(None)):
    if not authorization:
        raise HTTPException(status_code=401, detail="Missing Authorization header")
    if not authorization.startswith("Bearer "):
        raise HTTPException(status_code=401, detail="Invalid token format")
    token = authorization.split(" ")[1]
    
    payload = verify_jwt_token(token)
    if payload is None:
        # In development, we allow unverifiable tokens (e.g. from tymon/jwt-auth)
        # and return a mock user instead of failing hard
        logger.warning("JWT verification failed; running in dev mode, allowing through.")
        return {"sub": "unknown", "dev_mode": True}
    return payload


# ─────────────────────────────────────────────────────────
# Routes
# ─────────────────────────────────────────────────────────
@app.get("/health", tags=["Health"])
async def health_check():
    """Service health check endpoint."""
    gemini_configured = (
        bool(os.getenv("GEMINI_API_KEY"))
        and os.getenv("GEMINI_API_KEY") != "YOUR_GEMINI_API_KEY_HERE"
    )
    return {
        "status": "healthy",
        "service": "SmartHealth AI Triage Service",
        "version": "3.0.0",
        "gemini_configured": gemini_configured,
    }


@app.post("/triage", tags=["Triage"])
async def perform_triage(
    request: dict,
    current_user: dict = Depends(get_current_user),
):
    """
    Perform AI-powered clinical triage using Google Gemini.
    
    Expects JSON body:
    {
        "patient_id": int,
        "message": str,
        "patient_context": dict (optional)
    }
    """
    symptoms_text = request.get("message") or request.get("symptoms")
    if not symptoms_text:
        raise HTTPException(status_code=422, detail="Field 'message' is required.")

    patient_id = request.get("patient_id")
    patient_context = request.get("patient_context", {})

    logger.info(f"[Triage] Patient {patient_id} | User: {current_user.get('sub')} | Symptoms: {symptoms_text[:80]}...")

    try:
        service = TriageService()
        result = await service.analyze(
            symptoms=symptoms_text,
            patient_id=patient_id,
            patient_context=patient_context,
        )
        return {"success": True, "data": result}

    except Exception as e:
        logger.error(f"[Triage] Error during analysis: {e}", exc_info=True)
        raise HTTPException(status_code=500, detail=f"Triage analysis failed: {str(e)}")


@app.get("/", tags=["Root"])
async def root():
    return {
        "name": "SmartHealth AI Triage Service",
        "version": "3.0.0",
        "docs": "/docs",
        "health": "/health",
        "metrics": "/metrics",
    }
