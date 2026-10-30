"""Internal AI intent service. Never exposes data sources to the model."""
import os
from hmac import compare_digest
from typing import Annotated

from fastapi import Depends, FastAPI, Header, HTTPException

from .bedrock import interpret_bedrock
from .interpreter import interpret_mock
from .models import InterpretResponse, SearchRequest

app = FastAPI(title="AI Intent Service (Unofficial POC)", version="0.1.0")


def verify_internal_token(x_internal_token: Annotated[str | None, Header()] = None):
    expected = os.getenv("INTERNAL_AI_TOKEN", "")
    if not expected or not x_internal_token or not compare_digest(x_internal_token, expected):
        raise HTTPException(status_code=401, detail="Unauthorised internal request")


@app.get("/health")
def health():
    return {"status": "ok", "component": "ai-intent"}


@app.post("/v1/interpret", response_model=InterpretResponse, dependencies=[Depends(verify_internal_token)])
def interpret(request: SearchRequest):
    mode = os.getenv("AI_MODE", "mock").lower()
    if mode == "mock":
        return InterpretResponse(mode="mock", intent=interpret_mock(request.query))
    if mode == "bedrock":
        try:
            return InterpretResponse(mode="bedrock", intent=interpret_bedrock(request.query))
        except Exception:
            # Don't return provider exception text: could contain infrastructure details.
            raise HTTPException(status_code=502, detail="AI inference unavailable or invalid") from None
    raise HTTPException(status_code=503, detail="AI_MODE not configured")
