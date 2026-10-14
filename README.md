# TicketShield

TicketShield is an event ticket booking application built with Laravel, React, Inertia, and Tailwind CSS.

## Run With Docker

The development environment runs Laravel, Vite, and MySQL in containers. A local PHP, Composer, Node.js, npm, or MySQL installation is not required; install Docker Desktop (or Docker Engine with the Compose plugin) and make sure its daemon is running.

From the repository root, start the full environment:

```sh
docker compose up -d
```

Compose builds the PHP and Node images, starts MySQL, waits for the database health check, and starts the Laravel and Vite development servers. On first startup, the Laravel container creates `.env` from `.env.example` if needed, generates an application key, and runs all pending migrations.

Open the application at <http://localhost:8000>. Vite serves development assets and hot module replacement on port `5173`.

## Services And Defaults

| Service | Purpose | Local address |
| --- | --- | --- |
| `app` | Laravel development server and Artisan commands | <http://localhost:8000> |
| `vite` | Vite development server and HMR | <http://localhost:5173> |
| `db` | MySQL 8.0 | `localhost:3306` |

The default local database is `ticket`, with username `ticket` and password `ticket`. The MySQL root password defaults to `root`. These credentials are for local development only. Compose reads optional overrides from the root `.env`; `.env.example` contains the complete Laravel environment template and is copied automatically on first startup.

Login uses the Google reCAPTCHA v2 checkbox. Configure `GOOGLE_RECAPTCHA_SITE_KEY` and `GOOGLE_RECAPTCHA_SECRET_KEY` in `.env` with keys for your domain before logging in.

Source files are bind-mounted into the app and Vite containers, so code changes are available immediately. Composer packages, npm packages, and MySQL data use named Docker volumes and survive container restarts and `docker compose down`.

## Common Commands

```sh
# View service status and logs
docker compose ps
docker compose logs -f app vite db

# Run additional migrations or other Artisan commands
docker compose exec app php artisan migrate
docker compose exec app php artisan <command>

# Run the test suite
docker compose exec app php artisan test

# Stop containers while keeping database data
docker compose down

# Remove containers and all persisted volumes, including MySQL data
docker compose down -v
```

After changing `composer.lock`, `package-lock.json`, or a Dockerfile, rebuild the affected image:

```sh
docker compose up -d --build
```

If ports `8000`, `5173`, or `3306` are already in use, stop the process using the port before starting the stack.

## Screenshots

![TicketShield event listing](/public/screenshot/img-1.png)

![TicketShield event details](/public/screenshot/img-2.png)

![TicketShield booking interface](/public/screenshot/img-3.jpg)
