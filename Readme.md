# 🐳 Docker + PHP 8.3 + MySQL + Nginx + Mercure + RabbitMQ + Symfony 7 Boilerplate

## Description

This is a complete stack for running Symfony 7.0 in Docker containers using docker-compose tool.

It is composed of 7 containers:

- `nginx` - acting as the webserver.
- `php` - the PHP-FPM container with the 8.3 version of PHP.
- `db` - MySQL database container with MySQL 8.0 image.
- `mercure-hub` - Mercure Hub.
- `rabbit-mq` - RabbitMQ message broker.
- `mailer` - Mailpit testing mail server.
- `swagger-ui` - OpenAPI documentation.

This project follows Hexagonal architecture principles.

We implement a super-basic CRUD for *User* entity with user authentication and authorization. *User* has the
following properties:

- `id` - unique ID
- `email` - user email
- `password` - user password
- `firstName` - user first name
- `lastName` - user last name
- `createdAt` - user creation date
- `updatedAt` - user modification date (or NULL if user was never updated)
- `deletedAt` - soft-deletion date (or NULL if user is not deleted)

## Project structure

All controller entry points are located in `src/EntryPoint/Http/Controllers` folder.

Modules:

- `Shared` - classes designed for general use.
- `User` - classes related to user and authentication.

This is a pure **API** application. You can browse Open API docs (see below) to explore and test API responses.

We support two authentication methods:

- Mobile application, providing user authentication via **auth tokens** (similar to Laravel Sanctum auth tokens).
  A new token is generated after each successful registration or login. Client app should store this token in secure
  area and use it for all subsequent requests.
- Single page web application, providing user authentication via **JWT** with **token refresh** support. Client browser
  should store provided tokens in local storage and use them for all subsequent requests.

We support two types of users: **admin** user and **regular** user.

Depending on the authentication mode all subsequent requests should use one of these route patterns:

- `^/api/app/` - Auth token (mobile app)
- `^/api/web/` - JWT (browser)
- `^/api/admin/` - JWT (browser), endpoints for admin users only
- `^/api/app/(forgot-password|reset-password)` - special endpoints for mobile app users who wish to reset their
  passwords
- `^/api/web/(forgot-password|reset-password)` - special endpoints for Web SPA users who wish to reset their passwords

**Account actions (auth token):**

- User can register. If registration is successful user is automatically logged in on a device used for registration.
- User can log in on multiple devices. Each registered device provides its own token that can be used to access
  protected pages.
- User can log out from a given device.
- User can log out from all devices.
- User can change password.
- User can update profile (firstname and lastname).
- User can delete his account.

**Account actions (JWT):**

- User can register.
- User can log in. Each login generates token (JWT) and refresh token. JWT can be used to access protected pages.
  Refresh token will authorize new JWT generation when the current one expires.
- User can log out.
- User can change password.
- User can update profile (firstname and lastname).
- User can delete his account.

**User actions for administrator (JWT):**

- Admin can create user.
- Admin can edit all user data, including email and password.
- Admin can soft-delete user.
- Admin can restore (undelete) soft-deleted user.
- Admin can force-delete user (remove from DB).

**Reset password actions for all users:**

- User can request reset-password link email.
- User can reset password with her email and token received in reset-password email.

We leverage Symfony Mercure (SSE) to implement real-time updates for all connected UI clients. Whenever a user is
created, updated or deleted we publish Mercure update messages, so that all clients see updates without the need of
page reload.

## Installation

1. Clone this repo.
2. Go inside `./docker` folder and run `docker compose up --pull always --wait -d` to start containers
3. Browse OpenAPI docs: http://localhost:8888

*Note: `docker compose up -d` command executes entry point script (composer install, migrations if need be, etc.) that
may take some time on slow systems. If you get errors in Swagger "Try it out" shortly after starting docker containers,
please, wait for a while or check `php` container logs to make sure that all start up tasks are finished.*

## How to test

**Console commands should be executed inside `php` container.**

Run `docker exec -it php bash` or use your favourite Docker desktop application `php` container Exec tab.

Run tests: `#php ./vendor/bin/phpunit`.

By default, we use blazing-fast Sqlite in-memory database for testing (.env.test):

```
# Create .env.test.local file in project root and override DATABASE_URL for testing against MySQL database.
# DATABASE_URL=mysql://root:root@host.docker.internal:3306/symfony?serverVersion=8.0.33

DATABASE_URL="sqlite:///:memory:"
```

However, you can easily switch to MySQL database for testing:

- Create .env.test.local file in project root
- Add `DATABASE_URL=mysql://root:root@host.docker.internal:3306/symfony?serverVersion=8.0.33` setting to override
  default Sqlite option.

A default Admin user is created when you run docker containers for the first time.
You can use the following credentials right away:

- email: admin@example.com
- password: password

You can create more users running console command: `#php bin/console app:add-user`. Remember that console
commands should be executed inside docker `php` container.

You can use Swagger UI at http://localhost:8888 for testing selected API endpoints. Most endpoints require
authorization, so you will have to run registration / login first and then copy token from response to authorize
subsequent requests.

You can also test some API features using a compatible ReactJS [frontend](https://github.com/alex3493/symfony-react-ui)
project. It is preconfigured to use default http://localhost as API URL.

For testing *password reset* flow in frontend you have to configure frontend URL to get correct password reset link by
email. See `.env`: `FRONTEND_URL=http://localhost:8080`

This is a default value that will work if you run the frontend application in Docker. However, if you compile UI locally
with npm this value will most likely be `FRONTEND_URL=http://localhost:3001`.

See frontend readme for details.

Use Mailer container UI http://localhost:8025 to read password reset emails.

**Important note:** when using frontend application in development mode (compiled with Vite) make sure that you have API
Docker containers already running. Mercure Hub is configured to use port 3000, which is also the default port for React
UI. Vite automatically assigns next available port if 3000 is occupied. However, it will not work the other way
round: `docker compose up -d` will fail if port 3000 is not available.

## What's next

Currently, we have only one entity CRUD example - User entity. As far as this entity is also used for authentication, it
may be tricky to separate entity part from security part. We have to introduce another example entity, e.g. well-known
`ToDo`, where we can demonstrate CQRS and Event-driven design approaches.

You are free to fork this repo and add new features. Anyway, make sure that you rebuild containers after you do
important changes (e.g. new migrations, etc.) In local project folder cd to `.docker`, then:

- `docker compose down --remove-orphans`
- `docker compose build --no-cache` (optional, just to make sure we have fresh images)
- `docker compose up --pull always --wait -d`







