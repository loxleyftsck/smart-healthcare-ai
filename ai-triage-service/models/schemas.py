from pydantic import BaseModel, ConfigDict
from typing import List

class TriageRequest(BaseModel):
    message: str

class TriageResponse(BaseModel):
    intent: str
    response: str
    symptoms: List[str]
    severity: str  # LOW, MEDIUM, HIGH
    confidence: float
    recommendation: str
    
    model_config = ConfigDict(json_schema_extra={
        "example": {
            "intent": "emergency_triage",
            "response": "Please seek immediate medical attention.",
            "symptoms": ["chest pain", "shortness of breath"],
            "severity": "HIGH",
            "confidence": 0.95,
            "recommendation": "Go to ER immediately or call 119."
        }
    })
