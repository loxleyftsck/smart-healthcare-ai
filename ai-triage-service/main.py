from fastapi import FastAPI
from api.routes import router as api_router
from core.config import settings

app = FastAPI(
    title=settings.APP_NAME,
    description="Python AI Microservice for processing patient symptoms.",
    version="1.0.0",
)

# API routes
app.include_router(api_router, prefix="/api", tags=["triage"])

@app.get("/health", tags=["health"])
async def root():
    return {"status": "healthy", "service": settings.APP_NAME}

if __name__ == "__main__":
    import uvicorn
    uvicorn.run(app, host="0.0.0.0", port=settings.PORT)
