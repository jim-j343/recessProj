package forum.util;

import java.nio.charset.StandardCharsets;
import java.security.MessageDigest;
import java.security.NoSuchAlgorithmException;

/**
 * Local password hashing for the offline cache.
 *
 * The server remains the source of truth (bcrypt via Laravel). This is only
 * used so the desktop's local SQLite cache never stores a plaintext password —
 * it stores a salted SHA-256 digest and compares digests for offline login.
 */
public final class PasswordHash {

    // App-level salt. Not a substitute for per-user salts, but keeps the
    // on-disk cache from holding raw passwords. Server auth is unaffected.
    private static final String SALT = "aces::local::v1::";

    private PasswordHash() { }

    /** SHA-256 hex digest of the salted password. */
    public static String of(String password) {
        if (password == null) password = "";
        try {
            MessageDigest md = MessageDigest.getInstance("SHA-256");
            byte[] digest = md.digest((SALT + password).getBytes(StandardCharsets.UTF_8));
            StringBuilder sb = new StringBuilder(digest.length * 2);
            for (byte b : digest) sb.append(Character.forDigit((b >> 4) & 0xF, 16))
                                    .append(Character.forDigit(b & 0xF, 16));
            return sb.toString();
        } catch (NoSuchAlgorithmException e) {
            // SHA-256 is always present on the JVM; fall back defensively
            return "plain:" + password;
        }
    }
}
