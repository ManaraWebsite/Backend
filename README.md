# Manara Website — Backend

REST API backend for the Manara website, built with [Laravel](https://laravel.com). It serves blog-style posts, a dynamic form builder with public submissions, and a "Field Voices" testimonials section, behind Sanctum token authentication with `user` / `admin` roles.

## Tech Stack

- PHP 8.3+
- Laravel 13
- Laravel Sanctum — API token authentication
- PostgreSQL (production) / SQLite (local default)
- Vite + Tailwind CSS 4 — asset bundling
- PHPUnit — testing
- Laravel Pint — code style

## Features

**Auth**
- Token login/logout (`POST /login`, `POST /logout`)
- Role-based access (`user`, `admin`) enforced by the `admin` middleware (`app/Http/Middleware/EnsureUserIsAdmin.php`)

**Posts**
- Admin CRUD, slug-based, with cover image upload and `draft` / `published` status
- Publish / unpublish actions
- Public listing and reading of posts

**Forms**
- Admin builds forms made of ordered, typed fields (`text`, `email`, `select`, `radio`, …), optionally required, with per-field options
- Admin can duplicate a form
- Public can view a form by slug and submit answers (rate-limited to 5 submissions/minute)
- Admin can list and export a form's submissions

**Field Voices**
- Admin CRUD for testimonial entries (name, role, quote, image, published flag)
- Public listing of published entries

## Requirements

- PHP >= 8.3 with the extensions Laravel needs (`pdo`, `mbstring`, `fileinfo`, …)
- Composer
- Node.js + npm
- PostgreSQL (or SQLite for local development)

## Getting Started

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Set your database credentials in `.env` (defaults to SQLite; production uses `DB_CONNECTION=pgsql`), then:

```bash
php artisan migrate
php artisan db:seed        # optional — creates an admin user and sample data
php artisan storage:link   # needed for post cover images / field-voice images
npm install
npm run build               # or `npm run dev` while developing
```

Run everything (server, queue listener, log viewer, Vite) at once:

```bash
composer run dev
```

Or just the API server:

```bash
php artisan serve
```

### Seeded admin account

Running `php artisan db:seed` creates:

- email: `admin@example.com`
- password: `password`

## API Overview

All routes are prefixed with `/api`. Authenticated requests use a Sanctum bearer token: `Authorization: Bearer <token>`.

**Public**

| Method | Endpoint | Description |
|---|---|---|
| GET | `/api-test` | Health check |
| POST | `/login` | Log in, returns a bearer token |
| GET | `/posts` | List published posts |
| GET | `/posts/{post:slug}` | Show a post |
| GET | `/forms/{form:slug}` | Show a form's fields |
| POST | `/forms/{form:slug}/submit` | Submit answers to a form (throttled: 5/min) |
| GET | `/field-voices` | List published field voices |

**Authenticated** (`auth:sanctum`)

| Method | Endpoint | Description |
|---|---|---|
| GET | `/user` | Current authenticated user |
| POST | `/logout` | Revoke the current token |

**Admin** (`auth:sanctum` + `admin`, prefixed `/admin`)

| Method | Endpoint | Description |
|---|---|---|
| GET/POST | `/admin/posts` | List / create posts |
| GET/PUT/DELETE | `/admin/posts/{post:slug}` | Show / update / delete a post |
| POST | `/admin/posts/{post:slug}/publish` | Publish a post |
| POST | `/admin/posts/{post:slug}/unpublish` | Unpublish a post |
| GET/POST | `/admin/forms` | List / create forms |
| GET/PUT/DELETE | `/admin/forms/{form:slug}` | Show / update / delete a form |
| POST | `/admin/forms/{form:slug}/duplicate` | Duplicate a form |
| GET | `/admin/forms/{form:slug}/submissions` | List a form's submissions |
| GET | `/admin/forms/{form:slug}/submissions/export` | Export a form's submissions |
| GET/POST | `/admin/field-voices` | List / create field voices |
| PUT/DELETE | `/admin/field-voices/{field_voice}` | Update / delete a field voice |

## Testing

```bash
composer test
# or
php artisan test
```

## Code Style

```bash
./vendor/bin/pint
```

## Project Structure

```
app/Http/Controllers/          Public controllers (Posts, Forms, FieldVoices, Auth)
app/Http/Controllers/Admin/    Admin-only controllers
app/Http/Requests/             Form request validation
app/Http/Resources/            API resource transformers
app/Models/                    Eloquent models
database/migrations/           Schema
database/factories/            Model factories
database/seeders/              DatabaseSeeder
routes/api.php                 All API routes
```
