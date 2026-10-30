# RestFinder AI

**Unofficial engineering demonstration. All sample listings and prices are fictional.**

A small, layered proof of concept for **natural-language accommodation discovery**. It demonstrates how a Laravel-based travel platform can adopt an AI capability without granting a language model direct access to reservation, price or database systems.

## What is implemented

- **React + Vite**: responsive conversational search demo and result cards, plus a technical trace of structured intent.
- **Laravel 13 (PHP 8.3)**: public search API, input validation, an internal authenticated HTTP call, strictly validated AI filters, deterministic property queries, a sample stay-block check, and rate limiting.
- **MariaDB**: example property catalogue, nightly prices, amenities and synthetic unavailable nights (10 properties).
- **Python + FastAPI + Pydantic**: a separate AI intent service with a strict schema and internal token authentication.
- **AI modes**: `mock` extracts limited search criteria locally at zero inference cost; `bedrock` sends a tool-definition request to a selected Amazon Bedrock model.
- **Redis**: Laravel rate-limit backing cache. The result set itself is NOT cached to avoid stale availability.
- **Docker Compose**: development deployment, including isolated services.
- **Tests**: 10 Python tests, plus Laravel feature-test source; PHP syntax checked during artifact creation.

This is a **technology demonstration**, not a functional booking engine or a production design. The mock parser is intentionally limited and is not an LLM. It recognises a curated set of locations and amenities; Bedrock mode requires compatible account access and a model supporting tool use.

## Quick start (Docker)

Requirements: Docker Engine with the Compose plugin and internet access for the first image/dependency download.

```powershell
Add this to your .env file
# DEVELOPMENT ONLY. Generate a different secret for any shared deployment.
INTERNAL_AI_TOKEN= i43uht34987bqnbiw78w93282bdq87edq9dhbq07d3bq
# mock: free deterministic parser. bedrock: actual model; requires AWS credentials and model access.
# No need to add anything in here, keep as is.
AI_MODE=mock
BEDROCK_MODEL_ID=
AWS_REGION=af-south-1

Then Run:
docker compose up --build
```

Open **http://localhost:5173**. The first build downloads the standard Laravel Composer skeleton and frontend packages. The database is migrated and seeded automatically.

Try:

> Pet-friendly beachfront in Hermanus for 4 guests with parking, under R2,000 per night

Or test availability exclusions:

> Hermanus for 4 guests under R2,000 from 2026-11-12 to 2026-11-15

For the seeded example, *Hermanus Ocean Nook* has blocked nights on 13–14 November 2026 and should be excluded in that date range; the dates are fictional and intended only to demonstrate the logic.

Stop with `docker compose down`. To reset the sample database, run `docker compose down -v` (destructive to **this demo's** Docker volume; not required for normal shutdown).

### Local AI-only test without Docker

From the project root:

```powershell
cd ai-service
python -m pip install -r requirements-test.txt
$env:PYTHONPATH = "."
python -m pytest -q
```

Run the service alone:

```powershell
$env:AI_MODE = "mock"
$env:INTERNAL_AI_TOKEN = "replace-with-your-local-secret"
python -m uvicorn app.main:app --host 127.0.0.1 --port 8001
```

API docs at **http://localhost:8001/docs**, and `GET /health`.

### Laravel feature tests (in Docker)

```powershell
docker compose exec -e APP_ENV=testing -e DB_CONNECTION=sqlite -e DB_DATABASE=:memory: -e CACHE_STORE=array laravel php artisan test --filter=AiSearchTest
```

Note: the Laravel feature tests are included but **have not been executed in the artifact-building environment** because Docker/Composer was unavailable there. The PHP sources have passed `php -l` syntax checks.

## Optional: Amazon Bedrock

1. Confirm that your AWS account, IAM role, selected region and inference profile/model support `Converse` **and tool use**. Different models have different capabilities. See the official [Amazon Bedrock Converse documentation](https://docs.aws.amazon.com/bedrock/latest/userguide/conversation-inference.html).
2. Set `AI_MODE=bedrock` and `BEDROCK_MODEL_ID=<supported model or inference-profile ID>` in `.env`.
3. Supply role-based AWS credentials to the AI service. For local development, **only** use temporary credentials, and never commit `.env` or long-lived access keys. The Compose configuration passes short-lived `AWS_*` environment variables through if supplied.
4. Restart: `docker compose up --build`.

Bedrock is used **only to extract intent** via a declared `extract_search_filters` tool. It does not execute that tool, request property records, calculate prices or create bookings. Bad model output fails validation. If Bedrock fails, the API reports unavailability rather than secretly replacing it with the mock parser.

## HTTP API

`POST http://localhost:8000/api/ai-search`

```json
{
  "query": "Pet-friendly beachfront in Hermanus for 4 guests with parking, under R2,000 per night"
}
```

Typical response shape:

```json
{
  "request_id": "generated-request-id",
  "mode": "mock",
  "intent": {
    "destination": "Hermanus",
    "guests": 4,
    "max_price_zar": 2000,
    "amenities": ["parking", "beachfront"],
    "pets_allowed": true,
    "check_in": null,
    "check_out": null
  },
  "results": [{
    "id": 1,
    "name": "Hermanus Ocean Nook",
    "city": "Hermanus",
    "description": "Pet-friendly coastal cottage with ocean views and private braai.",
    "guest_capacity": 4,
    "price_per_night_zar": 1750,
    "amenities": ["parking", "wifi", "braai", "sea_view", "beachfront"],
    "pets_allowed": true
  }],
  "result_count": 1,
  "availability_checked": false,
  "disclaimer": "Synthetic demo properties and indicative nightly prices. Not actual listings or bookable offers."
}
```

Actual response IDs are generated, and result rows depend on the sample database. **No live availability or booking quotes are produced.**

## Exact project structure

```text
ai-discovery-poc/
  README.md
  .env.example
  compose.yaml
  docs/
    ARCHITECTURE.md
  ai-service/
    Dockerfile
    requirements.txt
    requirements-test.txt
    app/
      main.py
      models.py
      interpreter.py
      bedrock.py
    tests/test_interpreter.py
  laravel-docker/
    Dockerfile
  laravel-overlay/
    bootstrap/app.php
    routes/api.php
    config/ai.php
    app/Models/{Property,AvailabilityBlock}.php
    app/Services/PropertySearchService.php
    app/Http/Controllers/Api/AccommodationSearchController.php
    database/migrations/*
    database/seeders/DatabaseSeeder.php
    tests/Feature/AiSearchTest.php
  frontend/
    Dockerfile
    package.json
    vite.config.js
    index.html
    src/main.jsx
    src/styles.css
  scripts/start-laravel.sh
```

The full Laravel framework is installed **from the official Composer package at Docker build time**; this repository contains only the Laravel-specific application overlay to avoid shipping third-party framework source.

## Architecture, limitations and rollout

Read [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md) for the trust model, dataflow and production hardening notes.

## Validation performed when creating these files

- Python interpreter and API tests: **10 passed**.
- All provided Laravel PHP source files: **syntax checks passed**.
- React/Compose/Laravel end-to-end runtime: **not executed** in this environment; Docker/Composer weren't available.

**Git policy:** No repository was initialised and no Git state was changed. All Git commands remain entirely under your control.
