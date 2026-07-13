# Bookstore API

A REST API for managing a bookstore catalog — authors, genres, and books — built with Laravel 13.

## Features

- CRUD endpoints for authors, genres, and books, versioned under `/api/v1`
- Books belong to many authors and many genres (`author_book`, `book_genre` pivot tables)
- Filtering, sorting, and pagination on list endpoints
- Auto-generated, collision-safe slugs for genres and books
- Form Request validation, API Resources for responses, and Action classes for the book create/update flow

## Tech Stack

- PHP 8.5, Laravel 13
- SQLite (default local database)
- Pest 4 for testing
- Laravel Sanctum (installed, not yet applied to the catalog routes)

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
| POST      | `/api/v1/auth/register` | Register user                                                                                      |
| POST      | `/api/v1/auth/login`    | Login                                                                                              |
| DELETE    | `/api/v1/auth/logout`   | Logout                                                                                             |

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

- `app/Http/Controllers/V1` — API controllers
- `app/Http/Requests` — form request validation
- `app/Http/Resources` — API response shaping
- `app/Actions/Books` — book create/update logic (including author/genre attach & sync)
- `app/Models` — `Author`, `Genre`, `Book` (with slug generation on `Genre`/`Book`)
- `tests/Feature` — feature tests per controller
