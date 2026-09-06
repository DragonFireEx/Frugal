# Frugal — frontend

Vue 3 + TypeScript SPA: auth, categories, transactions, budgets, and a monthly dashboard chart.

## Getting started

```bash
# from the repo root
docker compose up --build
```

The dev server (Vite, with hot-reload) is then reachable at `http://localhost:5173`.

### Environment

`VITE_API_URL` — base URL of the backend API. Already set in the root `docker-compose.yml` to `http://localhost:8080/api`. Change it there if the backend runs elsewhere.

## Running tests

```bash
docker compose exec frontend npm run test
```

Runs the Vitest unit suite (composables and the `auth` store, with `api/client` mocked). Full views aren't mounted/tested at this stage — the logic worth covering lives in `stores/` and `composables/`.

## Type-checking / build

```bash
docker compose exec frontend npm run build
```

Runs `vue-tsc` then produces a production build in `dist/` (see the root `docker-compose.prod.yml` for how that build is actually served).

## PWA / offline

`vite-plugin-pwa` generates a manifest + service worker at build time (`npm run build`). The service worker precaches the app shell and uses a network-first strategy for `GET /api/*`, so opening the app offline still shows the last-fetched data — there's no offline-write support (mutations just fail offline like they would without a service worker). Not active in `npm run dev`; test it via the production build (`docker-compose.prod.yml`).

## Project structure

```
src/
  api/          axios client + JWT interceptor — the only place that talks HTTP directly
  stores/       Pinia stores, one per domain (auth, categories, transactions, budgets, stats)
  views/        route-level components, wired to stores ("smart")
  components/   small reusable, prop-in/event-out components ("dumb")
  composables/  shared reactive logic (currency/date formatting, API error handling)
  utils/        plain helper functions (e.g. client-side form validators)
  router/       route definitions + the auth guard
  types/        shared TypeScript types mirroring the backend's JSON shapes
```
