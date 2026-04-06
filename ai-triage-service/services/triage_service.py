import google.generativeai as genai
import json
import logging
from core.config import settings

logger = logging.getLogger("ai_triage")

# Configure Gemini
genai.configure(api_key=settings.GEMINI_API_KEY)

# Use gemini-2.5-flash as it's the recommended model
model = genai.GenerativeModel(
    model_name="gemini-2.5-flash",
    system_instruction="""You are a highly capable AI medical triage assistant. 
Your goal is to analyze the patient's message, extract symptoms, determine the severity of the situation (LOW, MEDIUM, HIGH), and provide a recommendation.
You MUST output your analysis in strictly valid JSON format with the following schema:
{
    "intent": "emergency_triage" | "symptom_check" | "general_inquiry",
    "severity": "LOW" | "MEDIUM" | "HIGH",
    "confidence": float (0.0 to 1.0),
    "symptoms": ["symptom1", "symptom2"],
    "response": "A polite, concise response to the user acknowledging their symptoms.",
    "recommendation": "A clinical recommendation based on severity."
}
If there are life-threatening keywords (chest pain, stroke, unconscious, severe bleeding, difficulty breathing), severity MUST be HIGH.
NEVER output Markdown formatting for JSON (no ```json ... ``` blocks). Just the raw JSON object.
"""
)

def analyze_symptoms(message: str) -> dict:
    """Analyze a patient message using Gemini API and return triage data."""
    if not settings.GEMINI_API_KEY:
        logger.error("GEMINI_API_KEY is missing.")
        raise ValueError("AI API Key not configured")

    try:
        response = model.generate_content(
            message,
            generation_config=genai.types.GenerationConfig(
                temperature=0.1, # Low temperature for more deterministic analysis
                response_mime_type="application/json"
            )
        )
        
        # Parse output
        raw_text = response.text.strip()
        triage_data = json.loads(raw_text)
        
        return triage_data
    except json.JSONDecodeError as e:
        logger.error(f"Failed to parse Gemini response as JSON: {e}")
        logger.error(f"Raw response: {raw_text}")
        raise ValueError("Invalid response format from AI")
    except Exception as e:
        logger.error(f"Gemini API error: {e}")
        raise
