# ACES Desktop — architecture (foundation)

Offline-first JavaFX client for the ACES platform. This document covers the
app skeleton wired in the first backend phase.

## Packages
- `forum` — `MainApp` (entry point; initialises the cache, routes to Login, or
  opens the dev gallery with `-Daces.dev=true`).
- `forum.app` — `SceneManager` (FXML navigation) and `Session` (current user / token).
- `forum.models` — domain POJOs: `User`, `Role` (student / lecturer / system_admin).
- `forum.config` — `DatabaseConfig` (local DB path `~/.aces/aces-local.db`, API base URL).
- `forum.database` — `SQLiteConnection` (schema init), `UserDao`, `LocalCacheDAO`
  (with a `sync_log` queue for offline-created rows).
- `forum.services` — `AuthService` (login/register against the local cache; mock
  fallback for now) and `SyncService` (stub for push/pull).
- `forum.controllers` — `LoginController`, `RegisterController`.
- `forum.ui` — reusable controls (`IconTextField`, `RevealPasswordField`).

## First vertical slice — auth + role routing
Login/Register are wired end to end: enter credentials → `AuthService` validates
against the local SQLite users table → `Session` starts → `SceneManager.showHomeFor(role)`
routes student → Forum Dashboard, lecturer → Quiz Center, admin → Admin Analytics.

Seeded demo accounts (password `password`): `student@aces.test`,
`lecturer@aces.test`, `admin@aces.test`. In mock mode any email is accepted and
uses the selected role, so the flow can be exercised before the API exists.

## Next steps
1. Add a token-auth REST API to the Laravel web-app (`routes/api.php` + Sanctum).
2. Add `ApiClient` (java.net.http) + Jackson DTOs; switch `AuthService` off mock mode.
3. Implement `SyncService` push/pull driven by `sync_log`.
4. Build the Forum (topics/posts) as the first offline-first feature.
