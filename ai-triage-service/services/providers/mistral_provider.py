"""
Mistral AI Provider — Mistral 7B Healthcare via Ollama (local GPU inference).
"""

import os
import json
import logging
import asyncio
from typing import Optional, Dict, Any

from .base_provider import AIProvider

logger = logging.getLogger("smarthealth-triage.provider.mistral")


class MistralProvider(AIProvider):
    """
    Mistral 7B Healthcare provider using Ollama for local GPU inference.
    
    Setup:
    1. Install Ollama: https://ollama.ai/
    2. Pull model: ollama pull mistral or ollama pull neural-chat
    3. Start Ollama: ollama serve (runs on localhost:11434)
    4. Set: AI_PROVIDER=mistral, OLLAMA_URL=http://localhost:11434
    """

    SEVERITY_LEVELS = ["LOW", "MEDIUM", "HIGH"]

    def __init__(self):
        self.ollama_url = os.getenv("OLLAMA_URL", "http://localhost:11434")
        self.model_name = os.getenv("MISTRAL_MODEL", "mistral")  # or neural-chat
        self.available = False
        
        # Try to verify Ollama connection
        self._verify_ollama()

    def _verify_ollama(self):
        """Check if Ollama is running and model is available."""
        try:
            import httpx

            with httpx.Client(timeout=5.0) as client:
                try:
                    resp = client.get(f"{self.ollama_url}/api/tags")
                    if resp.status_code == 200:
                        models = resp.json().get("models", [])
                        model_names = [m.get("name", "").split(":")[0] for m in models]
                        if self.model_name in model_names or any(self.model_name in m for m in model_names):
                            logger.info(f"✅ Ollama available with {self.model_name} model")
                            self.available = True
                        else:
                            logger.warning(f"⚠️  {self.model_name} not found. Available: {model_names}")
                            logger.info(f"   Install: ollama pull {self.model_name}")
                except Exception as e:
                    logger.warning(f"⚠️  Cannot connect to Ollama at {self.ollama_url}: {e}")
                    logger.info("   Make sure Ollama is running: ollama serve")
        except ImportError:
            logger.warning("httpx not installed. Install with: pip install httpx")

    async def analyze(
        self,
        symptoms: str,
        patient_id: Optional[int] = None,
        patient_context: Dict[str, Any] = None,
    ) -> Dict[str, Any]:
        """Analyze symptoms using Mistral via Ollama."""
        patient_context = patient_context or {}

        if not self.available:
            logger.warning("Mistral/Ollama not available, returning fallback")
            return self._fallback_analysis(symptoms)

        prompt = self._build_prompt(symptoms, patient_context)

        try:
            import httpx
            
            async with httpx.AsyncClient(timeout=120.0) as client:
                payload = {
                    "model": self.model_name,
                    "prompt": prompt,
                    "stream": False,
                    "temperature": 0.3,  # Lower temp = more consistent for medical
                }

                logger.debug(f"Calling Ollama {self.model_name} with prompt length: {len(prompt)}")

                resp = await client.post(
                    f"{self.ollama_url}/api/generate",
                    json=payload,
                    timeout=120.0
                )

                if resp.status_code != 200:
                    logger.error(f"Ollama error {resp.status_code}: {resp.text}")
                    return self._fallback_analysis(symptoms)

                result = resp.json()
                raw_text = result.get("response", "")
                logger.debug(f"Mistral response: {raw_text[:200]}")

                return self._parse_response(raw_text, symptoms)

        except Exception as e:
            logger.error(f"Mistral analysis failed: {e}")
            return self._fallback_analysis(symptoms)

    def _build_prompt(self, symptoms: str, patient_context: Dict[str, Any]) -> str:
        """Build clinical triage prompt for Mistral."""
        age = patient_context.get("age", "unknown")
        gender = patient_context.get("gender", "unknown")
        history = patient_context.get("medical_history", "none reported")

        return f"""You are a specialized clinical triage assistant for an emergency telemedicine platform.
Your goal is to analyze patient symptoms and categorize the urgency of care.

PATIENT PROFILE:
- Age: {age}
- Gender: {gender}
- Medical History: {history}

CURRENT SYMPTOMS:
{symptoms}

Respond ONLY with valid JSON (no markdown, no extra text):
{{
  "intent": "emergency_triage|symptom_check|general_inquiry",
  "severity": "HIGH|MEDIUM|LOW",
  "confidence": 0.5-1.0,
  "response": "short clinical summary",
  "recommendation": "immediate next step for the patient",
  "symptoms": ["tag1", "tag2"],
  "icd10_codes": [],
  "urgency_hours": 24
}}

Definitions: 
- HIGH: Life-threatening (e.g., chest pain, difficulty breathing, stroke signs). Immediate ER.
- MEDIUM: Needs evaluation within 24 hours (e.g., high fever, severe abdominal pain).
- LOW: Can be managed at home or scheduled for a routine visit.
"""

    def _parse_response(self, raw_text: str, original_symptoms: str) -> Dict[str, Any]:
        """Parse Mistral JSON response."""
        cleaned = raw_text.strip()
        
        # Try to extract JSON from response
        if "{" in cleaned and "}" in cleaned:
            start = cleaned.find("{")
            end = cleaned.rfind("}") + 1
            cleaned = cleaned[start:end]

        try:
            data = json.loads(cleaned)
            return self._validate_response(data)
        except json.JSONDecodeError:
            logger.error(f"Failed to parse Mistral JSON: {cleaned[:300]}")
            return self._fallback_analysis(original_symptoms)

    def _validate_response(self, data: Dict[str, Any]) -> Dict[str, Any]:
        """Validate and normalize response."""
        data.setdefault("intent", "symptom_check")
        data.setdefault("severity", "MEDIUM")
        data.setdefault("confidence", 0.7)
        data.setdefault("response", "Analysis complete.")
        data.setdefault("recommendation", "Consult healthcare provider.")
        data.setdefault("symptoms", [])
        data.setdefault("icd10_codes", [])
        data.setdefault("urgency_hours", 24)

        if data["severity"] not in self.SEVERITY_LEVELS:
            data["severity"] = "MEDIUM"

        return data

    def _fallback_analysis(self, symptoms: str) -> Dict[str, Any]:
        """Return safe fallback."""
        return {
            "intent": "symptom_check",
            "severity": "MEDIUM",
            "confidence": 0.5,
            "response": "Unable to analyze. Manual review needed.",
            "recommendation": "Contact healthcare professional.",
            "symptoms": symptoms.split()[:5],
            "icd10_codes": [],
            "urgency_hours": 12,
        }

    def is_available(self) -> bool:
        """Check if Mistral provider is available."""
        return self.available

    def get_status(self) -> Dict[str, Any]:
        """Get provider status."""
        return {
            "provider": "mistral",
            "available": self.available,
            "model": self.model_name,
            "ollama_url": self.ollama_url,
            "usage": "Light (~3.5GB VRAM), GPU-accelerated local inference",
        }
