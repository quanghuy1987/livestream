# Livestreaming Backend

A backend service for a livestream platform built with **pure PHP 7.2**, **MySQL**, and **Docker**. No framework — clean architecture with SOLID principles.

## Tech Stack

- **PHP 7.2** (Apache)
- **MySQL 8.0**
- **Composer** (PSR-4 autoloading)
- **PHPUnit 8** (automated tests)
- **Docker + Docker Compose**

## Architecture

```
src/
├── Domain/          # Entities, enums, exceptions, repository interfaces
├── Application/     # Use cases, DTOs, rate limiter interface
├── Infrastructure/  # MySQL repositories, rate limiter, idempotency store, audit logger
├── Http/            # Router, request/response, middleware, controllers
└── Cli/             # Archive command
```


---

## Getting Started

### Prerequisites

- [Docker](https://www.docker.com/get-started) and Docker Compose installed
- Ports `8080` and `3306` available on your machine

### 1. Clone and configure

```bash
git clone <your-repo-url>
cd livestream

# Copy environment file (defaults work out of the box)
cp .env.example .env
```

### 2. Start the stack

```bash
docker-compose up --build
```

This will:
- Build the PHP 7.2 Apache image and install Composer dependencies
- Start MySQL 8.0 and run all migrations automatically
- Seed 5 test users (2 streamers, 3 audience members)

The API is now live at **http://localhost:8080**.

### 3. Verify it's running

```bash
curl http://localhost:8080/audience/livestreams \
  -H "Authorization: Bearer audience-token-1"
```

Expected response:
```json
{"success":true,"data":{"data":[],"meta":{"total":0,"page":1,"limit":20,"total_pages":0}}}
```

---

## Seeded Test Users

| Token | Username | Role |
|---|---|---|
| `streamer-token-1` | streamer_alice | streamer |
| `streamer-token-2` | streamer_bob | streamer |
| `audience-token-1` | viewer_charlie | audience |
| `audience-token-2` | viewer_diana | audience |
| `audience-token-3` | viewer_eve | audience |

---

## Running Tests

```bash
# All tests
docker-compose exec app composer test

# Unit tests only (no database required)
docker-compose exec app composer test:unit
```

---

## Example Workflows

### Full Streamer Lifecycle

**1. Create a room**
```bash
curl -s -X POST http://localhost:8080/streamer/start_room \
  -H "Authorization: Bearer streamer-token-1" \
  -H "Content-Type: application/json" \
  -d '{"title": "My First Stream"}'
```
```json
{
  "success": true,
  "data": {
    "id": 1,
    "streamer_id": 1,
    "title": "My First Stream",
    "status": "CREATED",
    "viewer_count": 0,
    "started_at": null,
    "ended_at": null,
    "created_at": "2026-04-01 10:00:00",
    "updated_at": "2026-04-01 10:00:00"
  }
}
```

**2. Go live**
```bash
curl -s -X POST http://localhost:8080/streamer/go_live \
  -H "Authorization: Bearer streamer-token-1"
```
```json
{
  "success": true,
  "data": {
    "id": 1,
    "status": "LIVE",
    "started_at": "2026-04-01 10:05:00",
    ...
  }
}
```

**3. End the stream**
```bash
curl -s -X POST http://localhost:8080/streamer/close_room \
  -H "Authorization: Bearer streamer-token-1"
```
```json
{
  "success": true,
  "data": {
    "id": 1,
    "status": "ENDED",
    "ended_at": "2026-04-01 11:00:00",
    ...
  }
}
```

---

### Audience Workflow

**List active streams**
```bash
curl -s "http://localhost:8080/audience/livestreams?status=LIVE&sort=viewer_count" \
  -H "Authorization: Bearer audience-token-1"
```

**Join a stream** (stream must be LIVE)
```bash
curl -s -X POST http://localhost:8080/audience/livestreams/1/join \
  -H "Authorization: Bearer audience-token-1"
```
```json
{
  "success": true,
  "data": {
    "livestream": { "id": 1, "viewer_count": 1, ... },
    "joined_at": "2026-04-01 10:10:00"
  }
}
```

**Check stats**
```bash
curl -s http://localhost:8080/audience/livestreams/1/stats \
  -H "Authorization: Bearer audience-token-1"
```
```json
{
  "success": true,
  "data": {
    "livestream_id": 1,
    "title": "My First Stream",
    "status": "LIVE",
    "viewer_count": 1,
    "active_viewers": 1,
    "started_at": "2026-04-01 10:05:00",
    "ended_at": null
  }
}
```

**Leave a stream**
```bash
curl -s -X POST http://localhost:8080/audience/livestreams/1/leave \
  -H "Authorization: Bearer audience-token-1"
```

---

### Idempotency

Prevent duplicate rooms on network retry by sending an `Idempotency-Key` header. The same key always returns the same result without creating duplicate records.

```bash
# First call — creates the room
curl -s -X POST http://localhost:8080/streamer/start_room \
  -H "Authorization: Bearer streamer-token-2" \
  -H "Content-Type: application/json" \
  -H "Idempotency-Key: my-unique-key-abc123" \
  -d '{"title": "Idempotent Stream"}'

# Retry with the same key — returns the same response, no duplicate created
curl -s -X POST http://localhost:8080/streamer/start_room \
  -H "Authorization: Bearer streamer-token-2" \
  -H "Content-Type: application/json" \
  -H "Idempotency-Key: my-unique-key-abc123" \
  -d '{"title": "Idempotent Stream"}'
```

---

### Rate Limiting

- `POST /streamer/start_room` — **5 requests / minute** per user
- `POST /audience/livestreams/{id}/join` — **20 requests / second** per user

Exceeding the limit returns `429`:
```json
{
  "success": false,
  "message": "Rate limit exceeded for 'start_room'. Try again later."
}
```

---


## API Overview

| Method | Endpoint | Role | Description |
|---|---|---|---|
| `POST` | `/streamer/start_room` | streamer | Create a room |
| `POST` | `/streamer/go_live` | streamer | Start streaming |
| `POST` | `/streamer/close_room` | streamer | End the stream |
| `GET` | `/streamer/my_room` | streamer | Get current active room |
| `GET` | `/audience/livestreams` | any | List streams (paginated) |
| `GET` | `/audience/livestreams/{id}` | any | Get stream details |
| `POST` | `/audience/livestreams/{id}/join` | any | Join a stream |
| `POST` | `/audience/livestreams/{id}/leave` | any | Leave a stream |
| `GET` | `/audience/livestreams/{id}/stats` | any | Get viewer stats |

Full API documentation: see [API_DOCS.md](API_DOCS.md) or import `postman/livestream.postman_collection.json` into Postman.

---

