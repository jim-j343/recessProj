package forum.services;

import forum.api.ApiClient;
import forum.api.ApiException;
import forum.api.dto.AuthResponse;
import forum.database.UserDao;
import forum.models.Role;
import forum.models.User;

import java.io.IOException;

/**
 * Authentication for the desktop client — online-first with offline fallback.
 *
 * Flow:
 *   1. Call the Laravel token API. On success, cache the server user + password
 *      locally and return the account plus its Sanctum token.
 *   2. If the server rejects the request (bad credentials, validation, blacklist),
 *      that answer is authoritative — surface it.
 *   3. If the server is unreachable (offline), validate against the local SQLite
 *      cache so previously-synced users can still sign in. In MOCK_MODE an unknown
 *      email is accepted with the selected role so the UI can be exercised.
 */
public class AuthService {

    /** When offline and the email isn't cached, accept it (dev convenience). */
    private static final boolean MOCK_MODE = true;

    private final UserDao userDao = new UserDao();
    private final ApiClient api = new ApiClient();

    public static class AuthException extends Exception {
        public AuthException(String message) { super(message); }
    }

    /** Authentication outcome: the account and its API token (null when offline). */
    public record Result(User user, String token) { }

    public Result login(String email, String password, Role roleHint) throws AuthException {
        if (email == null || email.isBlank())        throw new AuthException("Please enter your email.");
        if (password == null || password.isBlank())  throw new AuthException("Please enter your password.");
        String normalized = email.trim().toLowerCase();

        // 1. Live API — authoritative when reachable.
        try {
            AuthResponse resp = api.login(normalized, password);
            User user = userDao.upsertFromServer(resp.user, password);
            return new Result(user, resp.token);
        } catch (ApiException rejected) {
            throw new AuthException(rejected.getMessage());
        } catch (IOException | InterruptedException offline) {
            if (offline instanceof InterruptedException) Thread.currentThread().interrupt();
            // server unreachable — fall through to the local cache
        }

        // 2. Offline fallback — local SQLite cache.
        User existing = userDao.findByEmail(normalized);
        if (existing != null) {
            if (!userDao.passwordMatches(normalized, password)) {
                throw new AuthException("Incorrect email or password.");
            }
            if ("blacklisted".equalsIgnoreCase(existing.getStatus())
                    || "suspended".equalsIgnoreCase(existing.getStatus())) {
                throw new AuthException("Your account is " + existing.getStatus() + ".");
            }
            return new Result(existing, null);
        }
        if (MOCK_MODE) {
            User mock = new User(0, normalized.split("@")[0], normalized,
                    roleHint == null ? Role.STUDENT : roleHint, "active");
            return new Result(mock, null);
        }
        throw new AuthException("Cannot reach the server, and no cached account was found.");
    }

    public Result register(String fullName, String email, String password, Role role)
            throws AuthException {
        if (email == null || email.isBlank())        throw new AuthException("Email is required.");
        if (password == null || password.isBlank())  throw new AuthException("Password is required.");
        String normalized = email.trim().toLowerCase();
        Role r = role == null ? Role.STUDENT : role;

        // 1. Live API.
        try {
            AuthResponse resp = api.register(fullName, normalized, password, r.db());
            User user = userDao.upsertFromServer(resp.user, password);
            return new Result(user, resp.token);
        } catch (ApiException rejected) {
            throw new AuthException(rejected.getMessage());
        } catch (IOException | InterruptedException offline) {
            if (offline instanceof InterruptedException) Thread.currentThread().interrupt();
            // server unreachable — create locally, queued for future sync
        }

        // 2. Offline fallback — create in the local cache only.
        if (userDao.findByEmail(normalized) != null) {
            throw new AuthException("That email is already registered.");
        }
        User created = userDao.insert(fullName, normalized, password, r);
        if (created == null) throw new AuthException("Could not create the account.");
        return new Result(created, null);
    }

    /** Best-effort server-side token revocation. */
    public void logout(String token) {
        api.logout(token);
    }
}
