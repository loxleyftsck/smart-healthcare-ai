"""
Auth Service — JWT verification for tokens issued by Laravel tymon/jwt-auth.
"""

import os
import logging
from typing import Optional

logger = logging.getLogger("smarthealth-triage.auth")


def verify_jwt_token(token: str) -> Optional[dict]:
    """
    Attempt to decode a JWT token issued by Laravel (tymon/jwt-auth).

    In development, if the JWT_SECRET is not set, we gracefully skip
    verification and return None (main.py will allow it through in dev mode).

    In production, JWT_SECRET MUST be set and tokens must be valid.
    """
    jwt_secret = os.getenv("JWT_SECRET", "")
    environment = os.getenv("ENVIRONMENT", "development")

    if not jwt_secret:
        if environment == "production":
            logger.error("JWT_SECRET not configured in production!")
            return None
        logger.warning("JWT_SECRET not set; skipping verification in development.")
        return None

    try:
        from jose import jwt, JWTError
        payload = jwt.decode(
            token,
            jwt_secret,
            algorithms=["HS256"],
            options={"verify_aud": False},  # tymon/jwt-auth doesn't set aud by default
        )
        logger.info(f"JWT verified for sub={payload.get('sub')}")
        return payload
    except Exception as e:
        logger.warning(f"JWT verification failed: {e}")
        return None
