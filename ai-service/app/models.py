"""Validated, strictly bounded AI -> application contract."""
from datetime import date
from typing import Literal

from pydantic import BaseModel, ConfigDict, Field, model_validator

Amenity = Literal["parking", "wifi", "pool", "braai", "sea_view", "beachfront"]


class SearchRequest(BaseModel):
    model_config = ConfigDict(extra="forbid")
    query: str = Field(min_length=5, max_length=500)


class SearchIntent(BaseModel):
    model_config = ConfigDict(extra="forbid")
    destination: str | None = Field(default=None, max_length=60)
    guests: int | None = Field(default=None, ge=1, le=20)
    max_price_zar: int | None = Field(default=None, ge=1, le=100000)
    amenities: list[Amenity] = Field(default_factory=list, max_length=6)
    pets_allowed: bool | None = None
    check_in: date | None = None
    check_out: date | None = None

    @model_validator(mode="after")
    def validate_dates(self):
        if (self.check_in is None) != (self.check_out is None):
            raise ValueError("Both check_in and check_out must be supplied together")
        if self.check_in and self.check_out and self.check_out <= self.check_in:
            raise ValueError("check_out must be later than check_in")
        self.amenities = list(dict.fromkeys(self.amenities))
        return self


class InterpretResponse(BaseModel):
    mode: Literal["mock", "bedrock"]
    intent: SearchIntent
