# Nursery Check-in API (Track A: Laravel)

A small API that answers one question: who is in this room right now?
It supports several nursery groups (tenants) on one platform. One tenant can never see or change another tenant's data.

## How to run

You need PHP 8.2 or newer and Composer.

```
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
php artisan serve
```

## How to run the tests

```
php artisan test
```

## Endpoints

Every request needs the header `X-Tenant-ID: <number>`.

| Method and path | What it does | Success |
|---|---|---|
| `POST /rooms/{room}/check-ins` with `{"child_id": 1}` | Checks a child into a room | 201 |
| `POST /check-ins/{id}/check-out` | Checks the child out | 200 |
| `GET /rooms/{room}/occupants` | Lists children in the room now | 200 |

Errors: 400 missing tenant header, 404 not found (including other tenants' data), 409 conflict, 422 bad input.

## Trying it by hand

There are no endpoints to create rooms or children (see "Left out"). To make some, run:

```
php artisan tinker
```
```php
app(App\Tenancy\TenantContext::class)->set(1);
App\Models\Room::create(['name' => 'Sunflowers']);
App\Models\Child::create(['name' => 'Ava']);
```

Then call the API with curl, for example:

```
curl -X POST http://127.0.0.1:8000/rooms/1/check-ins \
  -H "X-Tenant-ID: 1" -H "Content-Type: application/json" -H "Accept: application/json" \
  -d '{"child_id": 1}'
```

## How tenant isolation works

1. A middleware reads `X-Tenant-ID` and stores it for the request. No header, no request (400).
2. The models Room, Child and CheckIn use a trait that adds `WHERE tenant_id = current tenant` to every query. It also sets `tenant_id` on every new record. A developer cannot forget the filter, because it is on by default.
3. Route lookups (`{room}`, `{checkIn}`) go through the same filter. Another tenant's record is "not found", so the answer is 404, not 403. A 403 would tell the caller the record exists.
4. If no tenant is set, the code throws an error instead of returning everyone's data.
5. The `child_id` validation also checks the child belongs to the current tenant.

Tests in `tests/Feature/TenantIsolationTest.php` try six ways for tenant A to reach tenant B's data. All of them fail as they should.

## The decision: a child checked into a second room

**I reject the request with a 409 Conflict.** The message says which room the child is in now.

Why:
- It is the safest choice. Nothing changes without a person deciding it. If staff tap the wrong room by mistake, they find out straight away.
- The answer to "who is in this room right now?" stays trustworthy. Automatic moves could quietly leave wrong records, and a nursery needs to know where every child is.
- It is simple to change later. "Move automatically" or "move with a warning" can be added on top of this.

How it is enforced, in two layers:
- The code checks for an open check-in first.
- The database has a unique index on `child_id` where `checked_out_at` is empty. If two staff tap at the same moment, the database stops the second one, and the code turns that into the same 409.

The cost: staff need one extra step (check out, then check in). I think that is acceptable for a safety-related system.

## Assumptions

- Login is handled elsewhere. The tenant id in the header is trusted.
- Rooms and children already exist. The tests create them directly.
- Checking out a check-in that is already closed is an error (409).
- Checking a child into the same room twice is also a 409.
- The `/api` URL prefix is removed, so paths match the brief.

## What I would do with more time

- Add a "move with a warning" option for rooms, behind a flag.
- Add pagination for large rooms.
- Add a check-in history report for a child.
- Add room capacity limits.
- Replace the trusted header with real login and make sure the tenant comes from the login.
- Add endpoints and seed data for rooms and children.
- Run tests on PostgreSQL as well as SQLite.

## Left out on purpose

- Login and permissions (the brief says to assume them).
- Creating or editing rooms and children.
- Any user interface.
- Extra packages. The project uses only what Laravel includes.

## How I used AI tools

I used Claude to plan the steps and to draft the first version of the code and tests. I read and ran everything myself. The decision on the open question (reject with a 409) was mine, and I picked it after comparing the three options. The migration code came out badly formatted, so I fixed it with Laravel Pint. 

My editor also showed false warnings in the tenant trait, so I ran the code to check that it worked instead of trusting the warnings. I also confirmed that the database index and the code check both exist for a reason: the code gives a friendly message, and the index catches two requests at the same moment.

Afterwards I read through every file so I can explain it in the follow-up conversation, and I can change any part of it live.

## Running this on AWS (ECS/Fargate)

The service runs as one container image on ECS Fargate behind an Application Load Balancer, with at least two tasks in different availability zones.

**Container:** Nginx and PHP-FPM, the app code, and dependencies installed with `composer install --no-dev --optimize-autoloader`. No `.env` file and no secrets inside the image. It runs as a non-root user and has a health check on `/up`. Database migrations run as a separate one-off task during deploy, not every time a container starts.

**Secrets:** AWS Secrets Manager holds the app key and the database login. The task definition passes them in when the task starts. The task role can read only those secrets. Nothing is in the image or the repository.

**Database:** Amazon RDS for PostgreSQL in private subnets, with no public address. Its security group accepts connections only from the service's security group. The app connects over TLS. I would turn on Multi-AZ and automated backups.

**Logging:** JSON logs written to stdout and collected by CloudWatch Logs. Each line has a request id, tenant id, route, status and time taken. No child names or other personal data, because this is data about children.

**Alerts:** 5xx error rate, slow responses at the load balancer, unhealthy tasks, database CPU, connections and free storage, and unusual spikes in 404s or 409s, which could be a bug or someone probing other tenants' ids. Alarms go to the team through SNS.