from datetime import date

import pytest
from fastapi.testclient import TestClient
from pydantic import ValidationError

from app.interpreter import interpret_mock
from app.main import app
from app.models import SearchIntent


def test_extracts_filters():
    intent = interpret_mock(
        "Pet-friendly beachfront in Hermanus for 4 guests, "
        "parking, under R2,000/night"
    )

    assert intent.destination == "Hermanus"
    assert intent.guests == 4
    assert intent.max_price_zar == 2000
    assert intent.pets_allowed is True
    assert set(intent.amenities) == {
        "parking",
        "beachfront",
    }


@pytest.mark.parametrize(
    ("query", "expected_guests"),
    [
        ("Find accommodation in Hermanus for four people", 4),
        ("Find accommodation in Hermanus for 4 people", 4),
        ("Find accommodation for two guests", 2),
        ("Find accommodation for twelve adults", 12),
        ("Accommodation for vier mense", None),
    ],
)
def test_guest_count_in_words_or_digits(query, expected_guests):
    intent = interpret_mock(query)
    assert intent.guests == expected_guests


def test_does_not_invent_missing_fields():
    result = interpret_mock(
        "Find something interesting for my holiday"
    )

    assert result.destination is None
    assert result.check_in is None
    assert result.guests is None


def test_explicit_dates_only():
    result = interpret_mock(
        "Cape Town for 2 guests 2026-11-12 to 2026-11-15"
    )

    assert result.check_in == date(2026, 11, 12)
    assert result.check_out == date(2026, 11, 15)


def test_invalid_reversed_dates_rejected():
    with pytest.raises(ValidationError):
        interpret_mock(
            "Cape Town 2026-11-15 to 2026-11-12"
        )


def test_rejects_unauthorised_fields():
    with pytest.raises(ValidationError):
        SearchIntent.model_validate({
            "destination": "Hermanus",
            "sql": "DROP TABLE properties",
        })


def test_rejects_out_of_range_guests():
    with pytest.raises(ValidationError):
        SearchIntent(guests=200)


def test_rejects_invalid_amenities():
    with pytest.raises(ValidationError):
        SearchIntent(
            amenities=["allow_bookings_without_payment"]
        )


def test_internal_api_token_required(monkeypatch):
    monkeypatch.setenv(
        "INTERNAL_AI_TOKEN",
        "local-unit-test-secret",
    )
    monkeypatch.setenv("AI_MODE", "mock")

    client = TestClient(app)

    response = client.post(
        "/v1/interpret",
        json={"query": "Find in Hermanus"},
    )

    assert response.status_code == 401

    response = client.post(
        "/v1/interpret",
        headers={
            "X-Internal-Token": "local-unit-test-secret"
        },
        json={"query": "Find in Hermanus"},
    )

    assert response.status_code == 200
    assert response.json()["mode"] == "mock"


def test_limits_text_length(monkeypatch):
    monkeypatch.setenv(
        "INTERNAL_AI_TOKEN",
        "local-unit-test-secret",
    )

    client = TestClient(app)

    response = client.post(
        "/v1/interpret",
        headers={
            "X-Internal-Token": "local-unit-test-secret"
        },
        json={"query": "a" * 501},
    )

    assert response.status_code == 422


def test_model_failure_fails_closed(monkeypatch):
    monkeypatch.setenv(
        "INTERNAL_AI_TOKEN",
        "local-unit-test-secret",
    )
    monkeypatch.setenv("AI_MODE", "bedrock")
    monkeypatch.delenv(
        "BEDROCK_MODEL_ID",
        raising=False,
    )

    client = TestClient(app)

    response = client.post(
        "/v1/interpret",
        headers={
            "X-Internal-Token": "local-unit-test-secret"
        },
        json={"query": "Find a place in Hermanus"},
    )

    assert response.status_code == 502
    assert "BEDROCK" not in response.text