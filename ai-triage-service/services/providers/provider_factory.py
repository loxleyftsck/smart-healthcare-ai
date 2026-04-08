"""
Provider Factory — Select and instantiate AI provider based on configuration.
"""

import os
import logging
from typing import Dict, Any, Optional

from .base_provider import AIProvider
from .gemini_provider import GeminiProvider
from .mistral_provider import MistralProvider

logger = logging.getLogger("smarthealth-triage.provider_factory")


class ProviderFactory:
    """
    Factory for selecting AI providers.
    
    Configuration via environment variable:
    - AI_PROVIDER=gemini (default) — Google Gemini API
    - AI_PROVIDER=mistral — Mistral via Ollama (local GPU)
    """

    _providers: Dict[str, AIProvider] = {}
    _active_provider: Optional[AIProvider] = None

    @classmethod
    def get_provider(cls) -> AIProvider:
        """Get the active AI provider."""
        if cls._active_provider is None:
            cls._initialize_provider()
        return cls._active_provider

    @classmethod
    def _initialize_provider(cls):
        """Initialize the configured AI provider."""
        provider_name = os.getenv("AI_PROVIDER", "gemini").lower()

        logger.info(f"Initializing AI provider: {provider_name}")

        if provider_name == "mistral":
            cls._active_provider = MistralProvider()
            if not cls._active_provider.is_available():
                logger.warning("Mistral not available, falling back to Gemini")
                cls._active_provider = GeminiProvider()
        else:
            # Default to Gemini
            cls._active_provider = GeminiProvider()

        if not cls._active_provider.is_available():
            logger.error("⚠️  No AI provider available! Using mock mode.")
            cls._active_provider = MockProvider()

        logger.info(
            f"✅ Active provider: {cls._active_provider.get_status()['provider']}"
        )

    @classmethod
    def list_providers(cls) -> Dict[str, Dict[str, Any]]:
        """List all available providers and their status."""
        return {
            "gemini": GeminiProvider().get_status(),
            "mistral": MistralProvider().get_status(),
        }

    @classmethod
    def get_provider_info(cls) -> Dict[str, Any]:
        """Get current active provider info."""
        return cls.get_provider().get_status()


class MockProvider(AIProvider):
    """Fallback mock provider for testing."""

    async def analyze(self, symptoms: str, patient_id=None, patient_context=None):
        """Return mock analysis."""
        import asyncio
        await asyncio.sleep(0.3)

        severity = "MEDIUM"
        if any(kw in symptoms.lower() for kw in ["chest pain", "stroke", "breathing"]):
            severity = "HIGH"
        elif any(kw in symptoms.lower() for kw in ["fever", "pain"]):
            severity = "MEDIUM"
        else:
            severity = "LOW"

        return {
            "severity": severity,
            "confidence": 0.6,
            "response": f"Mock analysis of: {symptoms[:50]}",
            "recommendation": "Please see a healthcare provider.",
            "symptoms": symptoms.split()[:3],
            "icd10_codes": [],
            "urgency_hours": 24 if severity == "LOW" else 12 if severity == "MEDIUM" else 0,
        }

    def is_available(self) -> bool:
        return True

    def get_status(self) -> Dict[str, Any]:
        return {
            "provider": "mock",
            "available": True,
            "note": "Running in MOCK mode. No APIs configured.",
        }
