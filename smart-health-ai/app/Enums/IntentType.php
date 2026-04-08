<?php

namespace App\Enums;

/**
 * Chat Intent Types
 * 
 * Represents different types of user intents detected from chat messages.
 * Used for routing to appropriate response templates and handling strategies.
 */
enum IntentType: string
{
    case GREETING = 'greeting';
    case SYMPTOM_QUERY = 'symptom_query';
    case MEDICATION_ADVICE = 'medication_advice';
    case LIFESTYLE = 'lifestyle';
    case APPOINTMENT = 'appointment';
    case EMERGENCY = 'emergency';
    case FALLBACK = 'fallback';

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match($this) {
            self::GREETING => 'Greeting',
            self::SYMPTOM_QUERY => 'Symptom Query',
            self::MEDICATION_ADVICE => 'Medication Advice',
            self::LIFESTYLE => 'Lifestyle',
            self::APPOINTMENT => 'Appointment',
            self::EMERGENCY => 'Emergency',
            self::FALLBACK => 'General Inquiry',
        };
    }

    /**
     * Check if intent is emergency
     */
    public function isEmergency(): bool
    {
        return $this === self::EMERGENCY;
    }

    /**
     * Check if intent requires triage
     */
    public function requiresTriage(): bool
    {
        return $this === self::SYMPTOM_QUERY || $this === self::EMERGENCY;
    }
}
