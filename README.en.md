[🇯🇵 日本語](README.md) | [🇬🇧 English](README.en.md)

# TaikanBaseWeather (Perception-Based Weather)

A web app that displays current weather for all 47 prefectures in Japan and presents a ranking of "how hot/cold today is compared to the past 20 years."  
Backs up gut feelings like "it feels hot lately" or "is this year colder than usual?" with actual data from the Japan Meteorological Agency.

---

## Architecture

```
Scheduler (every 5 minutes)
    ↓
WeatherPoller (php artisan app:poll-weather)
    ↓ HTTP GET
JMA AMeDAS API (real-time weather)
    ↓
SQS (raw-events + DLQ)
    ↓
BulkQueueWorker
    ├── Save to weather_records (bulk insert)
    ├── Ranking calculation → cache update
    └── Extreme value alert (email notification)

MySQL 8 ← all tables
```

---

## Tech Stack

| Type | Technology |
|---|---|
| Backend | Laravel 12 / PHP 8.3 |
| Frontend | React 19 / Inertia.js 2.0 / Vite 8 |
| DB | MySQL 8 |
| Queue/Async | AWS SQS + BulkQueueWorker (bulk insert) |
| Cache | Laravel Cache (file / Redis) |
| Styling | Tailwind CSS v4 |
| Map | Leaflet.js / React-Leaflet |
| IaC | Terraform + LocalStack |

---

## Data Sources

| Data | Source | Use |
|---|---|---|
| Real-time weather | JMA AMeDAS API | Current temperature and precipitation |
| Past 20 years of daily temperatures | JMA Historical Weather Data Search | Ranking calculation |

---

## Key Features

| Feature | Description |
|---|---|
| Dashboard | Current weather at 47 locations nationwide + rank among past 20 years |
| Location Detail (Calendar) | Monthly calendar showing daily high temperature and rank as a heatmap |
| Admin Panel | SQS queue monitoring, DB write counts, load test execution |
| Alert Notifications | Email notification when extreme temperatures rank #1 in past 20 years |
| Load Testing | Simulation of bulk historical data ingestion (SQS bulk processing) |

---

## Local Development Setup

### Setup with Docker Compose (Recommended)

#### 1. Prerequisites
* Docker / Docker Desktop installed with the daemon running

#### 2. Initial Setup & Launch
```bash
docker compose up -d --build

docker compose exec app composer install
docker compose exec app npm install
docker compose exec app cp .env.docker .env
docker compose exec app php artisan key:generate

docker compose exec app php artisan migrate --seed
```

#### 3. Start Development Server (Vite)
```bash
docker compose exec app npm run dev
```

#### 4. Access URLs
* **Dashboard**: [http://localhost:8000](http://localhost:8000)
* **Mailpit (mail catcher)**: [http://localhost:8025](http://localhost:8025)

---

## Automated Testing

```bash
# Run tests
docker compose exec app php artisan test

# Code style check
docker compose exec app ./vendor/bin/pint --test

# Static analysis
docker compose exec app ./vendor/bin/phpstan analyse
```
