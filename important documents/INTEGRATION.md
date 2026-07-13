# Desktop ↔ Web API Integration

The JavaFX desktop client now authenticates against the Laravel web app over a
token-based REST API (Laravel Sanctum). Auth is **online-first with offline
fallback**: the desktop calls the API when the server is reachable, caches the
account locally, and falls back to the local SQLite cache when offline.

## What was added

### Web app (Laravel)
- `composer.json` — added `laravel/sanctum: ^4.0`.
- `bootstrap/app.php` — registered `routes/api.php` under the `/api` prefix.
- `config/sanctum.php` — Sanctum config (long-lived tokens for offline use).
- `database/migrations/..._create_personal_access_tokens_table.php` — token store.
- `app/Models/User.php` — added the `HasApiTokens` trait.
- `routes/api.php` — `POST /api/register`, `POST /api/login`, and (token-protected)
  `GET /api/user`, `POST /api/logout`.
- `app/Http/Controllers/Api/AuthController.php` — JSON auth returning `{ token, user }`.

### Desktop app (JavaFX)
- `forum.api.ApiClient` — `java.net.http` client calling the API.
- `forum.api.dto.AuthResponse` / `UserDto` — Jackson DTOs.
- `forum.api.ApiException` — raised when the server rejects a request.
- `forum.database.UserDao.upsertFromServer(...)` — caches the server user locally.
- `forum.services.AuthService` — online-first login/register with offline fallback,
  now returns a `Result(user, token)`.
- `LoginController` / `RegisterController` — store the returned token in `Session`.
- `module-info.java` — added `requires com.fasterxml.jackson.databind` and opened
  `forum.api.dto` to Jackson.

## One-time setup (run on your machine)

### 1. Web app
```bash
cd web-app
composer require laravel/sanctum:^4.0   # installs the package + updates the lock
php artisan migrate                     # creates personal_access_tokens
php artisan serve                       # serves the API at http://localhost:8000
```

### 2. Desktop app
```bash
cd desktop-app/ACESDesktop
mvn clean javafx:run
```
The client defaults to `http://localhost:8000/api`. Point it elsewhere with:
```bash
mvn clean javafx:run -Daces.api=http://<host>:<port>/api
```

## How it behaves
- **Server up:** login/register hit the API; the returned Sanctum token is stored in
  `Session`, and the account is cached in the local SQLite DB (with `server_id`).
- **Server rejects** (wrong password, validation error, blacklisted): the server's
  message is shown; no fallback.
- **Server down:** the client validates against the local cache; a previously
  synced account can still sign in offline (token is `null` until next online login).

## Quick API check
```bash
# Register
curl -X POST http://localhost:8000/api/register \
  -H "Accept: application/json" -H "Content-Type: application/json" \
  -d '{"username":"Jane Doe","email":"jane@uni.ac.ug","role":"student",
       "password":"password123","password_confirmation":"password123","rules_accepted":true}'

# Login
curl -X POST http://localhost:8000/api/login \
  -H "Accept: application/json" -H "Content-Type: application/json" \
  -d '{"email":"jane@uni.ac.ug","password":"password123"}'

# Authenticated call (paste the token from above)
curl http://localhost:8000/api/user \
  -H "Accept: application/json" -H "Authorization: Bearer <TOKEN>"
```

## Forum (topics + posts) — added

### API (token-protected, under `auth:sanctum`)
- `GET  /api/topics` — topics from the caller's active groups.
- `POST /api/topics` — `{ title, content, category?, group_id? }`; the content becomes
  the first post. `group_id` is resolved to the user's group if missing/invalid.
- `GET  /api/topics/{id}` — `{ topic, posts[] }`.
- `POST /api/topics/{id}/posts` — `{ content, parent_post_id? }`.

Implemented in `app/Http/Controllers/Api/ForumController.php`.

### Desktop behaviour (offline-first)
- **Forum Dashboard** renders cached topics instantly, then runs `SyncService.syncNow()`
  on a background thread (push queued rows → pull server topics) and re-renders.
- **New Topic** posts to the API when online (and caches the result); offline it creates
  the topic locally and queues it in `sync_log`.
- **Topic thread** pulls the latest posts when online; replies post to the API when
  online, otherwise queue locally. All network calls run off the JavaFX thread.

### SyncService
`push()` uploads pending `sync_log` rows (topics first, then their posts), records the
returned `server_id`, and marks each row `synced`. `pull()` fetches `/topics` and upserts
them into the cache. A missing token = offline = no-op.

## Hardening applied
- **Off the UI thread:** all API calls in the forum controllers run on daemon threads,
  updating the UI via `Platform.runLater`.
- **No plaintext passwords in the cache:** `forum.util.PasswordHash` stores a salted
  SHA-256 digest locally; `UserDao` compares digests (legacy plaintext rows still match
  for backward compatibility). The server remains bcrypt.
- **Logout:** clicking the profile chip on the Forum Dashboard clears the `Session` and
  revokes the token via `POST /api/logout` (best-effort), then returns to Login.

## Gotchas
- If offline login for the seeded demo users stops working after this update, delete the
  local cache so it re-seeds with hashed passwords — remove the file at
  `%USERPROFILE%\.aces\aces-local.db` (i.e. `~/.aces/aces-local.db`).
- Online topic creation needs at least one group to exist on the server. The API now
  auto-resolves a group, but if the database has **no** groups it returns a clear 422.

## Next steps
- Extend the same online-first + sync pattern to quizzes, groups, and participation.
- Add a background poll/'reconnect' trigger so queued items sync without reopening the
  dashboard.
- Move to per-user salts (or drop local password storage entirely) before shipping.
