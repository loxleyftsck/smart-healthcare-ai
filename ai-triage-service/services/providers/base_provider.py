"""
AI Provider Base Class — Abstract interface for different AI implementations.
"""

from abc import ABC, abstractmethod
from typing import Optional, Dict, Any
import logging

logger = logging.getLogger("smarthealth-triage.providers")


class AIProvider(ABC):
    """Base class for AI Triage providers."""

    @abstractmethod
    async def analyze(
        self,
        symptoms: str,
        patient_id: Optional[int] = None,
        patient_context: Dict[str, Any] = None,
    ) -> Dict[str, Any]:
        """
        Analyze symptoms and return structured triage result.
        
        Returns:
        {
            "severity": "LOW|MEDIUM|HIGH",
            "confidence": 0.0-1.0,
            "response": "clinical summary",
            "recommendation": "next action",
            "symptoms": ["tag1", "tag2"],
            "icd10_codes": ["code1", "code2"],
            "urgency_hours": int
        }
        """
        pass

    @abstractmethod
    def is_available(self) -> bool:
        """Check if provider is properly configured and available."""
        pass

    @abstractmethod
    def get_status(self) -> Dict[str, Any]:
        """Return provider status information."""
        pass
