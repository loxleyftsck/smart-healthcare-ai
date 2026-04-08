"""
AI Providers — Pluggable service providers for medical triage.

Available providers:
1. GeminiProvider — Google Gemini API (cloud, fast, high accuracy)
2. MistralProvider — Mistral 7B via Ollama (local GPU, ~3.5GB VRAM, privacy-friendly)

Select provider via AI_PROVIDER environment variable:
- AI_PROVIDER=gemini (default)
- AI_PROVIDER=mistral
"""

from .base_provider import AIProvider
from .gemini_provider import GeminiProvider
from .mistral_provider import MistralProvider
from .provider_factory import ProviderFactory

__all__ = [
    "AIProvider",
    "GeminiProvider",
    "MistralProvider",
    "ProviderFactory",
]
