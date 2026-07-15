# Bookstore API

A REST API for managing a bookstore catalog — authors, genres, books, and users — built with Laravel 13.

## Features

- CRUD endpoints for authors, genres, books, and users, versioned under `/api/v1`
- Books belong to many authors and many genres (`author_book`, `book_genre` pivot tables)
- Filtering, sorting, and pagination on list endpoints
- Auto-generated, collision-safe slugs for genres and books
- Form Request validation, API Resources for responses, and Action classes for the book create/update flow
- Role-based authorization: each user has a role (`admin`, `staff`, `client`) that maps to a fixed set of Sanctum token abilities (`App\Enums\Roles::permissions()`), granted at register/login time
- Every write endpoint (`store`/`update`/`destroy`) requires a Sanctum token with the matching ability (e.g. `author:create`, `book:delete`); author/genre/book reads are public, user management is admin-only end to end

## Tech Stack

- PHP 8.5, Laravel 13
- SQLite (default local database)
- Pest 4 for testing
- Laravel Sanctum for token auth and per-token abilities

## API Endpoints

Each resource exposes standard REST endpoints:

| Method    | URI                     | Description                                                                                        |
| --------- | ----------------------- | -------------------------------------------------------------------------------------------------- |
| GET       | `/api/v1/authors`       | List authors (filter by `name`, `last_name`, `nationality`, `birth_date`; sort/paginate)           |
| POST      | `/api/v1/authors`       | Create an author                                                                                   |
| GET       | `/api/v1/authors/{id}`  | Show an author                                                                                     |
| PUT/PATCH | `/api/v1/authors/{id}`  | Update an author                                                                                   |
| DELETE    | `/api/v1/authors/{id}`  | Delete an author                                                                                   |
| GET       | `/api/v1/genres`        | List genres (filter by `name`; sort/paginate)                                                      |
| POST      | `/api/v1/genres`        | Create a genre                                                                                     |
| GET       | `/api/v1/genres/{id}`   | Show a genre                                                                                       |
| PUT/PATCH | `/api/v1/genres/{id}`   | Update a genre                                                                                     |
| DELETE    | `/api/v1/genres/{id}`   | Delete a genre                                                                                     |
| GET       | `/api/v1/books`         | List books with authors/genres loaded (filter by title, isbn, published_year, etc.; sort/paginate) |
| POST      | `/api/v1/books`         | Create a book (requires `authors` and `genres` id arrays)                                          |
| GET       | `/api/v1/books/{id}`    | Show a book                                                                                        |
| PUT/PATCH | `/api/v1/books/{id}`    | Update a book (syncs `authors`/`genres` if provided)                                               |
| DELETE    | `/api/v1/books/{id}`    | Delete a book                                                                                      |
| GET       | `/api/v1/users`         | List users — admin only (filter by `name`, `email`, `role`; sort/paginate)                        |
| POST      | `/api/v1/users`         | Create a user — admin only                                                                        |
| GET       | `/api/v1/users/{id}`    | Show a user — admin only                                                                           |
| PUT/PATCH | `/api/v1/users/{id}`    | Update a user's name/email/role/password — admin only                                             |
| DELETE    | `/api/v1/users/{id}`    | Delete a user — admin only                                                                         |
| POST      | `/api/v1/auth/register` | Register user (always created with the `client` role)                                             |
| POST      | `/api/v1/auth/login`    | Login                                                                                              |
| DELETE    | `/api/v1/auth/logout`   | Logout (revokes the current token)                                                                 |

## Roles & Permissions

Every user has a role (`App\Enums\Roles`), and each role maps to a fixed list of Sanctum abilities granted to its token on register/login:

| Role     | Abilities                                                                    |
| -------- | ----------------------------------------------------------------------------- |
| `client` | `book:read`, `genre:read`, `author:read`                                     |
| `staff`  | Everything `client` has, plus `create`/`update`/`delete` on books, genres, and authors |
| `admin`  | `*` (every ability, including full user management)                          |

Only `admin` can manage users (`/api/v1/users/*`), since no role is granted `user:*` abilities explicitly.

## Getting Started

```bash
composer install
npm install

cp .env.example .env
php artisan key:generate

touch database/database.sqlite
php artisan migrate

php artisan serve
```

Or use the bundled dev script (server, queue listener, logs, and Vite together):

```bash
composer dev
```

## Running Tests

```bash
php artisan test
```

Tests run against an isolated in-memory SQLite database (see `phpunit.xml`) and won't touch your local `database.sqlite`.

## Project Structure

- `app/Http/Controllers/V1` — API controllers (`Auth/` holds register/login/logout)
- `app/Http/Requests` — form request validation
- `app/Http/Resources` — API response shaping
- `app/Actions/Books` — book create/update logic (including author/genre attach & sync)
- `app/Models` — `Author`, `Genre`, `Book`, `User` (with slug generation on `Genre`/`Book`)
- `app/Enums/Roles.php` — role definitions and their Sanctum ability lists
- `tests/Feature` — feature tests per controller
