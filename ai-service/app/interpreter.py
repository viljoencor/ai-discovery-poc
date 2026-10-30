"""Zero-cost, deterministic demonstration parser. Never presented as a real LLM."""

import re
from datetime import date

from .models import SearchIntent

DESTINATIONS = (
    "Plettenberg Bay",
    "Stellenbosch",
    "Cape Town",
    "Paternoster",
    "Hermanus",
    "Knysna",
    "Hout Bay",
)

NUMBER_WORDS = {
    "one": 1,
    "two": 2,
    "three": 3,
    "four": 4,
    "five": 5,
    "six": 6,
    "seven": 7,
    "eight": 8,
    "nine": 9,
    "ten": 10,
    "eleven": 11,
    "twelve": 12,
}

GUEST_PATTERN = re.compile(
    r"\b(\d{1,2}|" + "|".join(NUMBER_WORDS) + r")\s*"
    r"(?:people|persons|guests|adults|mense|gaste)\b",
    re.IGNORECASE,
)

AMENITY_PATTERNS = {
    "parking": r"\b(parking|garage|parkering)\b",
    "wifi": r"\b(wi[ -]?fi|internet)\b",
    "pool": r"\b(pool|swimming|swembad)\b",
    "braai": r"\b(braai|barbecue|bbq)\b",
    "sea_view": r"\b(sea[ -]?view|ocean[ -]?view|sea views|see[ -]?uitsig)\b",
    "beachfront": r"\b(beachfront|on the beach|strandfront|direk op die strand)\b",
}


def interpret_mock(query: str) -> SearchIntent:
    text = query.lower()

    destination = next(
        (city for city in DESTINATIONS if city.lower() in text),
        None,
    )

    guest_match = GUEST_PATTERN.search(text)
    guests = None

    if guest_match:
        value = guest_match.group(1).lower()
        guests = int(value) if value.isdigit() else NUMBER_WORDS[value]

    # Recognise budgets marked with R or ZAR.
    budget_match = re.search(
        r"(?:\bzar\s*|\br\s*)(\d[\d\s,]*(?:\.\d{1,2})?)\b",
        text,
        re.I,
    )

    budget = None

    if budget_match:
        budget = int(
            float(
                budget_match.group(1)
                .replace(",", "")
                .replace(" ", "")
            )
        )

    amenities = [
        key
        for key, pattern in AMENITY_PATTERNS.items()
        if re.search(pattern, text)
    ]

    pets = None

    if re.search(
        r"\b(no pets|not pet friendly|geen troeteldiere)\b",
        text,
    ):
        pets = False

    elif re.search(
        r"\b(pet[ -]?friendly|pets allowed|with (?:my )?dog|"
        r"honde welkom|troeteldiervriendelik)\b",
        text,
    ):
        pets = True

    # Require explicit ISO dates to avoid guessing.
    dates = re.findall(r"\b\d{4}-\d{2}-\d{2}\b", text)

    arrival = None
    departure = None

    if len(dates) >= 2:
        try:
            arrival = date.fromisoformat(dates[0])
            departure = date.fromisoformat(dates[1])
        except ValueError:
            pass

    return SearchIntent(
        destination=destination,
        guests=guests,
        max_price_zar=budget,
        amenities=amenities,
        pets_allowed=pets,
        check_in=arrival,
        check_out=departure,
    )