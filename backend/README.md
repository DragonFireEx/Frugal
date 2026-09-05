# Frugal — backend

Symfony 8 / PHP 8.4 API: JWT auth, categories, transactions, budgets, monthly stats.

## Requirements

- Docker + Docker Compose (see the root `docker-compose.yml`, run from the repo root)

## Getting started

```bash
# from the repo root
cp backend/.env.example backend/.env   # only needed if you'll run outside Docker
docker compose up --build
```

Generate the JWT signing keypair (once, before the first login/register works):

```bash
docker compose exec backend php bin/console lexik:jwt:generate-keypair
```

Run database migrations:

```bash
docker compose exec backend php bin/console doctrine:migrations:migrate
```

The API is then reachable through nginx at `http://localhost:8080/api`.

## Running tests

```bash
docker compose exec backend php bin/phpunit
```

Tests run against a separate `frugal_test` database (via Doctrine's `dbname_suffix` config), created once with:

```bash
docker compose exec database createdb -U frugal_user frugal_test
docker compose exec backend php bin/console doctrine:migrations:migrate --env=test --no-interaction
```

## Endpoints

All `/api/*` routes except `/api/register` and `/api/login` require `Authorization: Bearer <token>`.

```
POST   /api/register          {email, password, name} -> 201 {id, email, name}
POST   /api/login              {email, password} -> 200 {token}
GET    /api/me                 -> {id, email, name}

GET    /api/categories
POST   /api/categories         {name, type, color, icon}
PUT    /api/categories/{id}
DELETE /api/categories/{id}

GET    /api/transactions?month=YYYY-MM&categoryId=
POST   /api/transactions       {categoryId, amount, description, date}
PUT    /api/transactions/{id}
DELETE /api/transactions/{id}

GET    /api/stats/monthly?month=YYYY-MM -> {month, income, expense, balance, byCategory}

GET    /api/budgets
POST   /api/budgets            {categoryId, monthlyLimit}
PUT    /api/budgets/{id}
```

Every resource (`Category`, `Transaction`, `Budget`) is scoped to the logged-in user — `ownerId` in a request body is never trusted.

## Error format

All API errors are JSON:

```json
{ "error": "Komunikat", "code": 404 }
```

Validation failures additionally include field-level messages:

```json
{ "error": "Validation failed", "violations": { "email": "This value is not valid." } }
```
