"""
Triage Service — Google Gemini AI integration for clinical symptom analysis.
"""

import os
import json
import logging
import asyncio
from typing import Optional

logger = logging.getLogger("smarthealth-triage.service")


class TriageService:
    """
    Analyzes patient symptoms using Google Gemini AI and returns
    structured triage results with severity, recommendations, and
    extracted symptom tags.
    """

    SEVERITY_LEVELS = ["LOW", "MEDIUM", "HIGH"]

    def __init__(self):
        self.api_key = os.getenv("GEMINI_API_KEY", "")
        self.mock_mode = (
            not self.api_key or self.api_key == "YOUR_GEMINI_API_KEY_HERE"
        )
        if not self.mock_mode:
            try:
                import google.generativeai as genai
                genai.configure(api_key=self.api_key)
                self.model = genai.GenerativeModel("gemini-2.0-flash-exp")
                logger.info("✅ Gemini AI model initialized.")
            except ImportError:
                logger.warning("google-generativeai not installed; switching to mock mode.")
                self.mock_mode = True
        else:
            logger.warning("⚠️  No Gemini API key found. Running in MOCK mode.")

    async def analyze(
        self,
        symptoms: str,
        patient_id: Optional[int] = None,
        patient_context: dict = {},
    ) -> dict:
        """
        Analyze symptoms and return a structured triage result.
        """
        if self.mock_mode:
            return await self._mock_analysis(symptoms)

        return await self._gemini_analysis(symptoms, patient_context)

    async def _gemini_analysis(self, symptoms: str, patient_context: dict) -> dict:
        """Perform real Gemini AI analysis."""
        prompt = self._build_prompt(symptoms, patient_context)

        # Run blocking Gemini call in executor
        loop = asyncio.get_event_loop()
        response = await loop.run_in_executor(
            None, lambda: self.model.generate_content(prompt)
        )

        raw_text = response.text
        logger.info(f"Gemini raw response: {raw_text[:200]}")

        return self._parse_gemini_response(raw_text, symptoms)

    def _build_prompt(self, symptoms: str, patient_context: dict) -> str:
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

    def _parse_gemini_response(self, raw_text: str, original_symptoms: str) -> dict:
        """Parse Gemini response and extract structured JSON."""
        # Strip markdown code fences if present
        cleaned = raw_text.strip()
        if cleaned.startswith("```"):
            lines = cleaned.split("\n")
            cleaned = "\n".join(
                line for line in lines
                if not line.startswith("```")
            )

        try:
            data = json.loads(cleaned)
            # Validate required fields
            data.setdefault("severity", "MEDIUM")
            data.setdefault("confidence", 0.7)
            data.setdefault("response", "Analysis complete.")
            data.setdefault("recommendation", "Please consult a doctor.")
            data.setdefault("symptoms", [])
            data.setdefault("icd10_codes", [])
            data.setdefault("urgency_hours", 24)
            return data
        except json.JSONDecodeError:
            logger.error(f"Failed to parse Gemini JSON: {cleaned[:300]}")
            # Return a fallback with MEDIUM severity
            return {
                "severity": "MEDIUM",
                "confidence": 0.5,
                "response": "Unable to fully parse AI response. Manual review recommended.",
                "recommendation": "Please consult a healthcare professional immediately.",
                "symptoms": original_symptoms.split()[:5],
                "icd10_codes": [],
                "urgency_hours": 12,
            }

    async def _mock_analysis(self, symptoms: str) -> dict:
        """Return a deterministic mock result for dev/testing."""
        await asyncio.sleep(0.3)  # Simulate latency

        symptoms_lower = symptoms.lower()

        # Simple keyword-based severity
        high_keywords = ["chest pain", "shortness of breath", "stroke", "unconscious",
                        "severe bleeding", "heart attack", "can't breathe", "collapsed"]
        medium_keywords = ["fever", "vomiting", "persistent pain", "swelling",
                          "infection", "high temperature", "dizziness"]

        severity = "LOW"
        confidence = 0.82

        for kw in high_keywords:
            if kw in symptoms_lower:
                severity = "HIGH"
                confidence = 0.94
                break

        if severity == "LOW":
            for kw in medium_keywords:
                if kw in symptoms_lower:
                    severity = "MEDIUM"
                    confidence = 0.78
                    break

        recommendations = {
            "HIGH": "🚨 Go to the Emergency Room immediately or call 119.",
            "MEDIUM": "📅 Schedule an appointment with your doctor within 24 hours.",
            "LOW": "💊 Rest, stay hydrated, and monitor symptoms. Use telemedicine if symptoms worsen.",
        }

        responses = {
            "HIGH": "Patient presents high-severity symptoms requiring immediate emergency evaluation.",
            "MEDIUM": "Patient presents moderate symptoms that warrant timely medical evaluation.",
            "LOW": "Patient presents mild symptoms manageable with self-care and monitoring.",
        }

        # Extract simple symptom tags from input
        words = symptoms_lower.replace(",", " ").replace(".", " ").split()
        stop_words = {"i", "have", "been", "the", "a", "and", "or", "is", "with",
                     "my", "very", "since", "has", "am", "experiencing", "feeling"}
        tags = [w for w in words if len(w) > 3 and w not in stop_words][:6]

        return {
            "severity": severity,
            "confidence": confidence,
            "response": responses[severity],
            "recommendation": recommendations[severity],
            "symptoms": tags if tags else ["symptoms reported"],
            "icd10_codes": ["R05.9"] if severity == "LOW" else ["R06.0", "R07.9"],
            "urgency_hours": 0 if severity == "HIGH" else (24 if severity == "MEDIUM" else 72),
        }
