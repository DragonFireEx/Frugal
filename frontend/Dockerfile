# ---- build: compiles the production static bundle ----
FROM node:20-alpine AS build

WORKDIR /app

COPY package*.json ./
RUN npm install

COPY . .

# Vite bakes VITE_API_URL into the built bundle at build time, so it must be
# passed as a build ARG here — a runtime env var on the container has no effect.
ARG VITE_API_URL
ENV VITE_API_URL=$VITE_API_URL
RUN npm run build

# ---- production: serves the static bundle through nginx ----
FROM nginx:alpine AS production

COPY --from=build /app/dist /usr/share/nginx/html
COPY nginx.conf /etc/nginx/conf.d/default.conf

EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]

# ---- dev (default target): Vite dev server with hot-reload ----
# Kept last so plain `docker compose up --build` (no target override) keeps
# building this stage, unchanged from before this file grew extra stages.
FROM node:20-alpine AS dev

WORKDIR /app

COPY package*.json ./
RUN npm install

COPY . .

EXPOSE 5173

CMD ["npm", "run", "dev", "--", "--host", "0.0.0.0"]
