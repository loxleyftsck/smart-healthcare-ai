"""
Gemini AI Provider — Google Gemini 2.0 Flash integration.
"""

import os
import json
import logging
import asyncio
from typing import Optional, Dict, Any

from .base_provider import AIProvider

logger = logging.getLogger("smarthealth-triage.provider.gemini")


class GeminiProvider(AIProvider):
    """Google Gemini AI provider for clinical triage."""

    SEVERITY_LEVELS = ["LOW", "MEDIUM", "HIGH"]

    def __init__(self):
        self.api_key = os.getenv("GEMINI_API_KEY", "")
        self.available = False
        self.model = None

        if not self.api_key or self.api_key == "YOUR_GEMINI_API_KEY_HERE":
            logger.warning("⚠️  No Gemini API key configured.")
            return

        try:
            import google.generativeai as genai
            genai.configure(api_key=self.api_key)
            self.model = genai.GenerativeModel("gemini-2.0-flash-exp")
            self.available = True
            logger.info("✅ Gemini AI provider initialized.")
        except ImportError:
            logger.warning("google-generativeai not installed. Install with: pip install google-generativeai")
        except Exception as e:
            logger.error(f"Failed to initialize Gemini: {e}")

    async def analyze(
        self,
        symptoms: str,
        patient_id: Optional[int] = None,
        patient_context: Dict[str, Any] = None,
    ) -> Dict[str, Any]:
        """Analyze symptoms using Gemini."""
        patient_context = patient_context or {}

        if not self.available:
            logger.warning("Gemini not available, returning fallback response")
            return self._fallback_analysis(symptoms)

        prompt = self._build_prompt(symptoms, patient_context)

        try:
            # Run blocking call in executor
            loop = asyncio.get_event_loop()
            response = await loop.run_in_executor(
                None, lambda: self.model.generate_content(prompt)
            )

            raw_text = response.text
            logger.debug(f"Gemini response: {raw_text[:200]}")

            return self._parse_response(raw_text, symptoms)
        except Exception as e:
            logger.error(f"Gemini analysis failed: {e}")
            return self._fallback_analysis(symptoms)

    def _build_prompt(self, symptoms: str, patient_context: Dict[str, Any]) -> str:
        """Build the clinical triage prompt for Gemini."""
        age = patient_context.get("age", "unknown")
        gender = patient_context.get("gender", "unknown")
        history = patient_context.get("medical_history", "none reported")

        return f"""You are a senior AI clinical triage assistant in an emergency telemedicine platform.

PATIENT CONTEXT:
- Age: {age}
- Gender: {gender}
- Known Medical History: {history}

REPORTED SYMPTOMS:
{symptoms}

TASK:
Analyze the symptoms and return a JSON object with EXACTLY this structure (no markdown, no extra text):
{{
  "severity": "HIGH" | "MEDIUM" | "LOW",
  "confidence": 0.0-1.0,
  "response": "one-sentence clinical summary",
  "recommendation": "specific recommended next action",
  "symptoms": ["list", "of", "extracted", "symptom", "tags"],
  "icd10_codes": ["relevant ICD-10 codes if applicable"],
  "urgency_hours": integer (how many hours before patient must be seen; 0 = immediate)
}}

Rules:
- HIGH = life-threatening (chest pain, stroke signs, severe bleeding, difficulty breathing)
- MEDIUM = needs medical attention within 24 hours
- LOW = can be managed at home or via telemedicine
- Be conservative: when in doubt, escalate severity.
"""

    def _parse_response(self, raw_text: str, original_symptoms: str) -> Dict[str, Any]:
        """Parse Gemini JSON response."""
        cleaned = raw_text.strip()
        if cleaned.startswith("```"):
            lines = cleaned.split("\n")
            cleaned = "\n".join(
                line for line in lines
                if not line.startswith("```")
            )

        try:
            data = json.loads(cleaned)
            # Validate and set defaults
            data = self._validate_response(data)
            return data
        except json.JSONDecodeError:
            logger.error(f"Failed to parse Gemini JSON: {cleaned[:300]}")
            return self._fallback_analysis(original_symptoms)

    def _validate_response(self, data: Dict[str, Any]) -> Dict[str, Any]:
        """Validate and normalize AI response."""
        data.setdefault("severity", "MEDIUM")
        data.setdefault("confidence", 0.7)
        data.setdefault("response", "Analysis complete.")
        data.setdefault("recommendation", "Please consult a doctor.")
        data.setdefault("symptoms", [])
        data.setdefault("icd10_codes", [])
        data.setdefault("urgency_hours", 24)

        # Ensure severity is valid
        if data["severity"] not in self.SEVERITY_LEVELS:
            data["severity"] = "MEDIUM"

        return data

    def _fallback_analysis(self, symptoms: str) -> Dict[str, Any]:
        """Return safe fallback response."""
        return {
            "severity": "MEDIUM",
            "confidence": 0.5,
            "response": "Unable to analyze. Manual review recommended.",
            "recommendation": "Please consult a healthcare professional.",
            "symptoms": symptoms.split()[:5],
            "icd10_codes": [],
            "urgency_hours": 12,
        }

    def is_available(self) -> bool:
        """Check if Gemini provider is configured and ready."""
        return self.available

    def get_status(self) -> Dict[str, Any]:
        """Get provider status."""
        return {
            "provider": "gemini",
            "available": self.available,
            "model": "gemini-2.0-flash-exp",
            "api_configured": bool(self.api_key),
        }
