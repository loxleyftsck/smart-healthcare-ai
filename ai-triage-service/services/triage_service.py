"""
Triage Service — AI-powered clinical symptom analysis.
Supports multiple AI providers (Gemini, Mistral, etc.)
"""

import os
import json
import logging
import asyncio
from typing import Optional

from .providers import ProviderFactory

logger = logging.getLogger("smarthealth-triage.service")


class TriageService:
    """
    Analyzes patient symptoms using configured AI provider
    (Gemini, Mistral, or mock) and returns structured triage results.
    """

    def __init__(self):
        self.provider = ProviderFactory.get_provider()
        provider_info = self.provider.get_status()
        logger.info(f"TriageService initialized with provider: {provider_info}")

    async def analyze(
        self,
        symptoms: str,
        patient_id: Optional[int] = None,
        patient_context: dict = {},
    ) -> dict:
        """
        Analyze symptoms and return a structured triage result.
        Delegates to the active AI provider.
        """
        logger.info(f"Analyzing symptoms for patient {patient_id}: {symptoms[:80]}...")
        
        result = await self.provider.analyze(
            symptoms=symptoms,
            patient_id=patient_id,
            patient_context=patient_context,
        )
        
        logger.info(f"Triage result: severity={result.get('severity')}, confidence={result.get('confidence')}")
        return result

    def get_provider_status(self) -> dict:
        """Get current provider status."""
        return self.provider.get_status()

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
