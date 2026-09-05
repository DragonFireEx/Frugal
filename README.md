# Frugal

Frugal is self-hosted expense tracker built with Vue 3, TypeScript and Symfony. Track transactions, organize spending by category, set monthly budgets, and visualize your finances with charts. Dockerized setup with PostgreSQL -> clone, run, and go.

## Stack

- **Backend**: Symfony 8 / PHP 8.4, Doctrine ORM, PostgreSQL 16, JWT auth (`lexik/jwt-authentication-bundle`)
- **Frontend**: Vue 3 + TypeScript, Vite, Pinia, vue-router, axios, Chart.js
- **Infra**: Docker Compose (nginx, PHP-FPM, Postgres, Vite dev server)

## Getting started

```bash
git clone <this-repo>
cd Frugal
docker compose up --build
```

Then, one-time setup (generate JWT keys and run migrations):

```bash
docker compose exec backend php bin/console lexik:jwt:generate-keypair
docker compose exec backend php bin/console doctrine:migrations:migrate
```

- Frontend: http://localhost:5173
- API: http://localhost:8080/api

## Documentation

- [`backend/README.md`](backend/README.md) — API endpoints, running tests, error format
- [`frontend/README.md`](frontend/README.md) — project structure, running tests

## License

MIT — see [`LICENSE`](LICENSE).
