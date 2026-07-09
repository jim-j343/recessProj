package forum.services;

import forum.database.UserDao;
import forum.models.Role;
import forum.models.User;

/**
 * Authentication for the desktop client.
 *
 * Current behaviour (design phase): validates against the local SQLite cache.
 * Seeded demo accounts (student@/lecturer@/admin@aces.test, password "password")
 * work out of the box. Unknown emails are accepted in mock mode so the UI flow
 * can be exercised — swap {@code MOCK_MODE} off once the sync API is wired.
 */
public class AuthService {

    private static final boolean MOCK_MODE = true;

    private final UserDao userDao = new UserDao();

    public static class AuthException extends Exception {
        public AuthException(String message) { super(message); }
    }

    /** Authenticate by email + password; {@code roleHint} comes from the login role selector. */
    public User login(String email, String password, Role roleHint) throws AuthException {
        if (email == null || email.isBlank())     throw new AuthException("Please enter your email.");
        if (password == null || password.isBlank()) throw new AuthException("Please enter your password.");

        User existing = userDao.findByEmail(email);
        if (existing != null) {
            if (!userDao.passwordMatches(email, password)) {
                throw new AuthException("Incorrect email or password.");
            }
            if ("blacklisted".equalsIgnoreCase(existing.getStatus())
                    || "suspended".equalsIgnoreCase(existing.getStatus())) {
                throw new AuthException("Your account is " + existing.getStatus() + ".");
            }
            return existing;
        }

        if (MOCK_MODE) {
            // No local record yet — accept and use the selected role (dev convenience).
            return new User(0, email.split("@")[0], email,
                    roleHint == null ? Role.STUDENT : roleHint, "active");
        }
        throw new AuthException("Incorrect email or password.");
    }

    public User register(String fullName, String email, String password, Role role) throws AuthException {
        if (email == null || email.isBlank())      throw new AuthException("Email is required.");
        if (password == null || password.isBlank()) throw new AuthException("Password is required.");
        if (userDao.findByEmail(email) != null)     throw new AuthException("That email is already registered.");
        return userDao.insert(fullName, email, password, role == null ? Role.STUDENT : role);
    }
}
