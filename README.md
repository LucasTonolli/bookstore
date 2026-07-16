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
- Self-service profile endpoints let any authenticated user view/update their own name, email, and password (password changes require the current password), independent of role
- Email verification via Laravel's built-in signed-URL flow, adapted to return JSON: request a link, then confirm it
- Forgot/reset password via Laravel's Password broker: requesting a link never reveals whether the email is registered, and a successful reset revokes all of the user's existing Sanctum tokens
- Session management: a user can list their own active Sanctum tokens and revoke one or all of them (their current token is protected from self-revocation through this endpoint)

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
| POST      | `/api/v1/auth/forgot-password` | Request a password reset link (1/min; always responds success, regardless of whether the email exists) |
| POST      | `/api/v1/auth/reset-password` | Reset the password with `token`/`email`/`password` (10/min; revokes all of the user's tokens on success) |
| GET       | `/api/v1/profile`       | Return the authenticated user's own data                                                          |
| PUT       | `/api/v1/profile`       | Update the authenticated user's own name/email (`role` in the payload is ignored)                 |
| PUT       | `/api/v1/profile/password` | Update the authenticated user's own password (requires `current_password`)                     |
| GET       | `/api/v1/profile/tokens` | List the authenticated user's own active tokens                                                    |
| DELETE    | `/api/v1/profile/tokens/{id}` | Revoke a specific token (400 if it's the token making the request)                             |
| DELETE    | `/api/v1/profile/tokens` | Revoke all of the user's tokens except the one making the request                                  |
| POST      | `/api/v1/email/verification-notification` | Resend the email verification link (throttled, 6/min)                            |
| GET       | `/api/v1/email/verify/{id}/{hash}` | Confirm the signed verification link                                                     |

## Roles & Permissions

Every user has a role (`App\Enums\Roles`), and each role maps to a fixed list of Sanctum abilities granted to its token on register/login:

| Role     | Abilities                                                                    |
| -------- | ----------------------------------------------------------------------------- |
| `client` | `book:read`, `genre:read`, `author:read`                                     |
| `staff`  | Everything `client` has, plus `create`/`update`/`delete` on books, genres, and authors |
| `admin`  | `*` (every ability, including full user management)                          |

Only `admin` can manage users (`/api/v1/users/*`), since no role is granted `user:*` abilities explicitly. The `/api/v1/profile/*` endpoints are the exception — they only require a valid Sanctum token (no specific ability), since they always act on the token's own owner rather than an arbitrary user.

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
- `app/Listeners/DeleteUserTokens.php` — revokes a user's Sanctum tokens on `PasswordReset`
- `tests/Feature` — feature tests per controller
